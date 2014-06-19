<?php if(!defined('VB_ENTRY')) die('Access denied.');

/* ======================================================================*\
  || #################################################################### ||
  || # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
  || # ---------------------------------------------------------------- # ||
  || # Copyright �2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
  || # This file may not be redistributed in whole or significant part. # ||
  || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
  || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
  || #################################################################### ||
  \*====================================================================== */

class vB_Xml_Import_PageTemplate extends vB_Xml_Import
{
	/**
	 * Widgets referenced by instances in the imported template
	 * @var array
	 */
	protected $referencedWidgets = array();

	/**
	 * Checks if all referenced widgets are already defined
	 * Also sets referencedWidgets class attribute to be used while importing
	 */
	protected function checkWidgets()
	{
		$requiredWidgets = array();

		$pageTemplates = is_array($this->parsedXML['pagetemplate'][0]) ? $this->parsedXML['pagetemplate'] : array($this->parsedXML['pagetemplate']);
		foreach ($pageTemplates AS $pagetemplate)
		{
			if (isset($pagetemplate['widgets']))
			{

				//We can get a single widget definition, or nothing, or an array of widget definitions.
				if (empty($pagetemplate['widgets']['widgetinstance']) OR !is_array($pagetemplate['widgets']['widgetinstance']))
				{
					continue;
				}
				else if (!empty($pagetemplate['widgets']['widgetinstance']['widgetguid']))
				{
					$widgetInstances = array($pagetemplate['widgets']['widgetinstance']);
				}
				else if (empty($pagetemplate['widgets']['widgetinstance'][0]))
				{
					continue;
				}
				else
				{
					$widgetInstances = $pagetemplate['widgets']['widgetinstance'];
				}

				foreach ($widgetInstances AS $instance)
				{
					$requiredWidgets[] = $instance['widgetguid'];

					if (isset($instance['subModules']))
					{
						if (empty($instance['subModules']['widgetinstance']) OR !is_array($instance['subModules']['widgetinstance']))
						{
							continue;
						}
						else if (!empty($instance['subModules']['widgetinstance']['widgetguid']))
						{
							$subModules = array($instance['subModules']['widgetinstance']);
						}
						else if (empty($instance['subModules']['widgetinstance'][0]))
						{
							continue;
						}
						else
						{
							$subModules = $instance['subModules']['widgetinstance'];
						}

						foreach ($subModules as $subModule)
						{
							$requiredWidgets[] = $subModule['widgetguid'];
						}
					}
				}
			}
		}

		$existingWidgets = $this->db->getRows('widget', array('guid' => $requiredWidgets));

		foreach ($existingWidgets AS $widget)
		{
			$this->referencedWidgets[$widget['guid']] = $widget;
		}

		$missingWidgets = array_diff($requiredWidgets, array_keys($this->referencedWidgets));

		if (!empty($missingWidgets))
		{
			throw new Exception('Reference to undefined widget(s): ' . implode(' ', $missingWidgets));
		}
	}

	protected function import()
	{
		if (empty($this->parsedXML['pagetemplate']))
		{
			$this->parsedXML['pagetemplate'] = array();
		}
		
		$this->checkWidgets();

		// get all columns but the key
		$pageTemplateTable = $this->db->fetchTableStructure('pagetemplate');
		$pageTemplateTableColumns = array_diff($pageTemplateTable['structure'], array($pageTemplateTable['key']));

		$widgetInstanceTable = $this->db->fetchTableStructure('widgetinstance');
		$widgetInstanceTableColumns = array_diff($widgetInstanceTable['structure'], array($pageTemplateTable['key'], $widgetInstanceTable['key']));

		$pageTemplates = is_array($this->parsedXML['pagetemplate'][0]) ? $this->parsedXML['pagetemplate'] : array($this->parsedXML['pagetemplate']);

		// get the config items defined for each widget
		$widgetDefinitionCache = array();
		$widgetRows = vB::getDbAssertor()->getRows('widget');
		$widgetGuids = array();
		foreach ($widgetRows AS $widgetRow)
		{
			$widgetGuids[$widgetRow['widgetid']] = $widgetRow['guid'];
		}
		$widgetDefRows = vB::getDbAssertor()->getRows('widgetdefinition');
		foreach ($widgetDefRows AS $widgetDefRow)
		{
			$widgetGuid = $widgetGuids[$widgetDefRow['widgetid']];
			if (!isset($widgetDefinitionCache[$widgetGuid]))
			{
				$widgetDefinitionCache[$widgetGuid] = array();
			}
			$widgetDefinitionCache[$widgetGuid][] = $widgetDefRow;
		}
		unset($widgetDefRows, $widgetDefRow, $widgetRows, $widgetRow, $widgetRows, $widgetGuids, $widgetGuid);

		foreach ($pageTemplates AS $pageTemplate)
		{
			$values = array();
			foreach($pageTemplateTableColumns AS $col)
			{
				if (isset($pageTemplate[$col]))
				{
					$values[$col] = $pageTemplate[$col];
				}
			}

			$pageTemplateId = $oldTemplateId = 0;
			$condition = array('guid' => $pageTemplate['guid']);
			if ($oldPageTemplate = $this->db->getRow('pagetemplate', $condition))
			{
				if ($this->options & self::OPTION_OVERWRITE)
				{
					$oldTemplateId = $oldPageTemplate['pagetemplateid'];

					// overwrite preexisting record
					$this->db->delete('pagetemplate', $condition);
				}
				else
				{
					$pageTemplateId = $oldPageTemplate['pagetemplateid'];
				}
			}

			if (empty($pageTemplateId))
			{
				$pageTemplateId = $this->db->insertIgnore('pagetemplate', $values);
			}

			if (is_array($pageTemplateId))
			{
				$pageTemplateId = array_pop($pageTemplateId);
			}

			// continue only if the widget could be inserted
			if ($pageTemplateId)
			{
				if ($oldTemplateId AND ($pageTemplateId != $oldTemplateId))
				{
					// update pages that point to the old templateid
					$this->db->update('page', array('pagetemplateid' => $pageTemplateId), array('pagetemplateid' => $oldTemplateId));
				}

				if ($this->options & self::OPTION_OVERWRITE)
				{
					// if we are overwriting the template with the same templateid, remove associated widget instances
					$this->db->delete('widgetinstance', array('pagetemplateid' => $pageTemplateId));
				}

				if (isset($pageTemplate['widgets']) AND
						(
							/* page template is new */
							(!$oldPageTemplate) OR
							/* we set the addwidgets flag */
							($this->options & self::OPTION_ADDWIDGETS)
						)
				   )
				{
					$widgets = is_array($pageTemplate['widgets']['widgetinstance'][0]) ? $pageTemplate['widgets']['widgetinstance'] : array($pageTemplate['widgets']['widgetinstance']);

					foreach ($widgets as $widget)
					{
						$values = array();
						foreach($widgetInstanceTable['structure'] AS $col)
						{
							if (isset($widget[$col]))
							{
								if ($col == 'adminconfig' AND $widget[$col] != '')
								{
									// default admin config values are defined for this widget instance
									// in the vbulletin-pagetemplates.xml file. When setting these, make
									// sure we also pull in any additional default config items
									// for this widget
									$adminConfig = $widget[$col];
									if (($temp = unserialize($adminConfig)) !== false)
									{
										$adminConfig = $temp;
										unset($temp);
									}
									$defaultConfig = array();
									$configItems = $widgetDefinitionCache[$widget['widgetguid']];
									
									if (!empty($configItems))
									{
										foreach ($configItems AS $configItem)
										{
											if (!empty($configItem['name']))
											{
												$defaultConfig[$configItem['name']] = $configItem['defaultvalue'];
											}
										}
									}
									unset($configItems, $configItem);

									$values[$col] = serialize($adminConfig + $defaultConfig);
								}
								else
								{
									$values[$col] = $widget[$col];
								}
							}
						}
						$values['widgetid'] = $this->referencedWidgets[$widget['widgetguid']]['widgetid'];
						$values['pagetemplateid'] = $pageTemplateId;
						$widgetInstanceId = $this->db->insert('widgetinstance', $values);
						if (is_array($widgetInstanceId))
						{
							$widgetInstanceId = array_pop($widgetInstanceId);
						}

						if (isset($widget['subModules']))
						{
							$subModules = is_array($widget['subModules']['widgetinstance'][0]) ? $widget['subModules']['widgetinstance'] : array($widget['subModules']['widgetinstance']);

							foreach($subModules AS $widget)
							{
								$values = array();
								foreach($widgetInstanceTable['structure'] AS $col)
								{
									if (isset($widget[$col]))
									{
										$values[$col] = $widget[$col];
									}
								}
								$values['parent'] = $widgetInstanceId;
								$values['widgetid'] = $this->referencedWidgets[$widget['widgetguid']]['widgetid'];
								$values['pagetemplateid'] = $pageTemplateId;
								$this->db->insert('widgetinstance', $values);
							}
						}
					}
				}
			}

			vB_Xml_Import::setImportedId(vB_Xml_Import::TYPE_PAGETEMPLATE, $pageTemplate['guid'], $pageTemplateId);

			// Insert phrases for pagetemplate title.
			$phraseLib = vB_Library::instance('phrase');
			$guidforphrase = $phraseLib->cleanGuidForPhrase($pageTemplate['guid']);
			$phraseLib->save('pagemeta',
				'pagetemplate_' . $guidforphrase . '_title',
				array(
					'text' => array($pageTemplate['title']),
					'ismaster' => 1,
					'product' => 'vbulletin',
					't' => 0,
					'oldvarname' => 'pagetemplate_' . $guidforphrase . '_title',
					'oldfieldname' => 'pagemeta',
				)
			);

		}
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
