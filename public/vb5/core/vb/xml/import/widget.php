<?php if(!defined('VB_ENTRY')) die('Access denied.');

/* ======================================================================*\
  || #################################################################### ||
  || # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
  || # ---------------------------------------------------------------- # ||
  || # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
  || # This file may not be redistributed in whole or significant part. # ||
  || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
  || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
  || #################################################################### ||
  \*====================================================================== */

class vB_Xml_Import_Widget extends vB_Xml_Import
{
	protected function import()
	{
		// get all columns but the key
		$widgetTable = $this->db->fetchTableStructure('widget');
		$widgetTableColumns = array_diff($widgetTable['structure'], array($widgetTable['key']));
		
		$widgetDefinitionTable = $this->db->fetchTableStructure('widgetdefinition');
		
		if (empty($this->parsedXML['widget']))
		{
			$this->parsedXML['widget'] = array();
		}
		
		$widgets = is_array($this->parsedXML['widget'][0]) ? $this->parsedXML['widget'] : array($this->parsedXML['widget']);
		
		foreach ($widgets AS $widget)
		{
			$values = array();
			foreach($widgetTableColumns AS $col)
			{
				if (($col == 'product') AND !empty($this->productid))
				{
					$values[$col] = $this->productid;
				}
				else if (isset($widget[$col]))
				{
					$values[$col] = $widget[$col];
				}
				else if (($col == 'canbemultiple') OR ($col == 'cloneable') OR ($col == 'isthirdparty'))
				{
					$values[$col] = 0;
				}
				else
				{
					$values[$col] = '';
				}
			}
			$widgetid = $oldWidgetId = 0;
			$condition = array('guid' => $widget['guid']);

			if ($oldWidget = $this->db->getRow('widget', $condition))
			{
				if ($this->options & self::OPTION_OVERWRITE)
				{
					// Delete existing
					$oldWidgetId = $oldWidget['widgetid'];
					$this->db->delete('widget', $condition);
					$this->db->delete('widgetdefinition', array('widgetid' => $oldWidgetId));
				}
				else
				{
					$widgetid = $oldWidget['widgetid'];
				}
			}
			
			if (!$widgetid)
			{
				$widgetid = $this->db->insert('widget', $values);
			}

			if (is_array($widgetid))
			{
				$widgetid = array_pop($widgetid);
			}
			
			// Continue only if we have a widget
			if ($widgetid)
			{
				if ($oldWidgetId AND ($widgetid != $oldWidgetId))
				{
					// update pages that point to the old templateid
					$this->db->update('widgetinstance', array('widgetid' => $widgetid), array('widgetid' => $oldWidgetId));
				}
				
				if (isset($widget['definitions']))
				{
					// Remove preexisting definitions
					$this->db->delete('widgetdefinition', array('widgetid'=>$widgetid));
					
					$definitions = is_array($widget['definitions']['definition'][0]) ? $widget['definitions']['definition'] : array($widget['definitions']['definition']);
					
					foreach ($definitions as $definition)
					{
						$values = array();
						foreach($widgetDefinitionTable['structure'] AS $col)
						{
							if (isset($definition[$col]))
							{
								$values[$col] = $definition[$col];
							}
						}
						$values['widgetid'] = $widgetid;
						$this->db->insert('widgetdefinition', $values);
					}
				}
			}
			
			vB_Xml_Import::setImportedId(vB_Xml_Import::TYPE_WIDGET, $widget['guid'], $widgetid);
		}
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
