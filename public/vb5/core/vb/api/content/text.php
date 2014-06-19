<?php
if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
   || #################################################################### ||
   || # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
   || # ---------------------------------------------------------------- # ||
   || # Copyright 2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
   || # This file may not be redistributed in whole or significant part. # ||
   || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
   || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
   || #################################################################### ||
   \*======================================================================*/


/**
 * vB_Api_Content_Text
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id$
 * @access public
 */
class vB_Api_Content_Text extends vB_Api_Content
{
	//override in client- the text name
	protected $contenttype = 'vBForum_Text';

	//The table for the type-specific data.
	protected $tablename = 'text';

	//When we parse the page.
	protected $bbcode_parser = false;

	//Whether we change the parent's text count- 1 or zero
	protected $textCountChange = 1;

	//Whether we handle showapproved,approved fields internally or not
	protected $handleSpecialFields = 0;

	//for spam checking
	protected $spamType = false;
	protected $spamKey = false;
	protected $akismet;

	//Does this content show author signature?
	protected $showSignature = true;

	//Is text required for this content type?
	protected $textRequired = true;

	/** normal protector- protected to prevent direct instantiation **/
	protected function __construct()
	{
		parent::__construct();
		$this->library = vB_Library::instance('Content_Text');
		//see if we have spam checking set.
		if (isset($this->options['vb_antispam_type']) AND $this->options['vb_antispam_type'] > 0 AND !empty($this->options['vb_antispam_key']))
		{
			$this->spamType = $this->options['vb_antispam_type'];
			$this->spamKey = $this->options['vb_antispam_key'];
		}
	}

	/*** Permanently deletes a node
	 *	@param	integer	The nodeid of the record to be deleted
	 *
	 *	@return	boolean
	 ***/
	public function delete($nodeid)
	{
		$node = $this->getContent($nodeid);

		if (!$this->validate($node, self::ACTION_DELETE, $nodeid))
		{
			throw new vB_Exception_Api('no_permission');
		}
		return $this->library->delete($nodeid);
	}

	/*** Adds a new node.
	 *
	 *	@param	mixed		Array of field => value pairs which define the record.
	 *  @param	array		Array of options for the content being created.
	 *						Available options include:
	 *							- nl2br: if TRUE, all \n will be converted to <br /> so that it's not removed by the html parser (e.g. comments).
	 *
	 * 	@return	integer		the new nodeid
	 ***/
	public function add($data, $options = array())
	{
		if ($this->textRequired AND empty($data['pagetext']) AND empty($data['rawtext']))
		{
			throw new vB_Exception_Api('text_required');
		}

		if (!$this->textRequired AND empty($data['pagetext']) AND empty($data['rawtext']))
		{
			// the duplicate check is based on the post text, which is not required,
			// so we need to skip it if there is no text
			$options['skipDupCheck'] = true;
		}

		$vboptions = vB::getDatastore()->getValue('options');
		$parentNode = vB_Api::instanceInternal('node')->getNode($data['parentid']);

		if (!empty($data['title']))
		{
			$strlen= vB_String::vbStrlen(trim($data['title']), true);
			if ($strlen > $vboptions['titlemaxchars'])
			{
				throw new vB_Exception_Api('maxchars_exceeded_x_title_y', array($vboptions['titlemaxchars'], $strlen));
			}
		}
		else
		{
			$channelcontentypeid = vB_Api::instanceInternal('contenttype')->fetchContentTypeIdFromClass('Channel');
			//title is requred for topics. VMs look like topics so they need to be exempt
			if ($parentNode['contenttypeid'] == $channelcontentypeid AND ($data['parentid'] != vB_Api::instanceInternal('node')->fetchVMChannel()))
			{
				throw new vB_Exception_Api('title_required');
			}
		}
		$isComment = ($parentNode['parentid'] == $parentNode['starter']);

		if ($isComment)
		{
			$minChars = $vboptions['commentminchars'];
			$maxChars = $vboptions['commentmaxchars'];
		}
		else
		{
			$minChars = $vboptions['postminchars'];
			$maxChars = $vboptions['postmaxchars'];
		}

		$strlen = vB_String::vbStrlen($this->library->parseAndStrip(empty($data['pagetext']) ? $data['rawtext'] : $data['pagetext']), true);

		if ($this->textRequired AND $strlen < $minChars)
		{
			throw new vB_Exception_Api('please_enter_message_x_chars', $minChars);
		}

		if($maxChars != 0 AND $strlen > $maxChars)
		{
			throw new vB_Exception_Api('maxchars_exceeded_x_y', array($maxChars, $strlen));
		}

		// If node is a starter and has no title
		if (!empty($data['starter']) AND ($data['starter'] != $data['parentid']) AND empty($data['title']))
		{
			return false;
		}

		if (isset($data['userid']))
		{
			unset($data['userid']);
		}

		if (isset($data['authorname']))
		{
			unset($data['authorname']);
		}

		if (!$this->validate($data, vB_Api_Content::ACTION_ADD))
		{
			throw new vB_Exception_Api('no_create_permissions');
		}

		//We shouldn't pass the open or show open fields
		unset($data['open']);
		unset($data['showopen']);

		//We shouldn't pass the approved or showapproved open fields
		if (!$this->handleSpecialFields)
		{
			unset($data['approved']);
			unset($data['showapproved']);
		}

		$nodeOptions = vB_Api::instanceInternal('node')->getOptions();
		$moderateNode = false;

		if ($parentNode['starter'] == 0)
		{
			$moderateNode = ($nodeOptions['moderate_topics'] & $parentNode['nodeoptions']) ? true : false;
		}
		else if (($nodeOptions['moderate_topics'] & $parentNode['nodeoptions'])
			OR ($nodeOptions['moderate_comments'] & $parentNode['nodeoptions']))
		{
			$moderateNode = true;
		}

		if (!vB::getUserContext()->getChannelPermission('forumpermissions', 'followforummoderation', $data['parentid'])
			OR $moderateNode)
		{
			$data['approved'] = 0;
			$data['showapproved'] = 0;
		}

		if (!isset($data['htmlstate']))
		{
			// We don't have a front end interface for this yet. So if they have permission to use HTML, give them HTML.
			// @TODO update this when we have a proper interface
			$data['htmlstate'] = 'on_nl2br';
		}

		$this->cleanInput($data);
		$this->cleanOptions($options);
		return $this->library->add($data , $options);
	}

	/*** updates a record
	 *
	 *	@param	mixed		array of nodeid's
	 *	@param	mixed		array of permissions that should be checked.
	 *
	 * 	@return	boolean
	 ***/
	public function update($nodeid, $data)
	{
		if (!vB::getUserContext()->getChannelPermission('forumpermissions2', 'canusehtml', $data['parentid']))
		{
			// Regardless of this node's previous htmlstate, if the user doesn't have permission to use html, turn it off.
			$data['htmlstate'] = 'off';
		}
		return parent::update($nodeid, $data);
	}

	/** THis returns a string with quoted strings in bbcode format.
	*
	*	@param	mixed	array of integers
	*
	* 	@return	string
	***/

	public function getQuotes($nodeids)
	{
		return $this->library->getQuotes($nodeids);
	}

	public function getIndexableFromNode($node, $include_attachments = true)
	{
		$all_content = parent::getIndexableFromNode($node, $include_attachments = true);
		array_unshift($all_content, $node['rawtext']);
		return $all_content;
	}

	/**
	 * Adds content info to $result so that merged content can be edited.
	 * @param array $result
	 * @param array $content
	 */
	public function mergeContentInfo(&$result, $content)
	{
		if (vb::getUserContext()->getChannelPermission('forumpermissions', 'canviewthreads', $result['nodeid']))
		{
			$this->library->mergeContentInfo($result, $content);
		}
	}

	/** Gets the data the presentation layer needs to have to parse the rawtext.
	 *
	 *	@param		mixed	nodeId or array of nodeIds
	 *
	 *	@return		mixed	array includes bbcodeoptions, attachments, and rawtext
	 */
	public function getDataForParse($nodeIds)
	{

		if (is_int($nodeIds))
		{
			$nodeIds = array($nodeIds);
		}
		else if (!is_array($nodeIds))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		$results = array();
		$options = vB::getDatastore()->getValue('bf_misc_forumoptions');
		$pmType = vB_Types::instance()->getContentTypeID('vBForum_PrivateMessage');
		$galleryTypeid = vB_Types::instance()->getContentTypeId('vBForum_Gallery');
		$userContext = vB::getUserContext();

		if (!empty($nodeIds))
		{
			$nodes = $this->assertor->assertQuery('vBForum:getDataForParse', array('nodeid' => $nodeIds));

			foreach ($nodes AS $node)
			{

				try
				{
					if ($userContext->getChannelPermission('forumpermissions', 'canviewthreads', $node['nodeid']))
					{
						if ($this->validate($node, self::ACTION_VIEW, $node['nodeid'], array($node))  )
						{
							$attachments = array();
							// We don't need to show attachments for gallery. See VBV-6389.

							if ($galleryTypeid != $node['contenttypeid'])
							{
								$attachments = $this->nodeApi->getNodeAttachments($node['nodeid']);
							}

							if ($node['contenttypeid'] == $pmType)
							{
								$bbCodeOptions = vB_Api::instance('content_privatemessage')->getBbcodeOptions();
							}
							else if ($userContext->getChannelPermission('forumpermissions', 'canviewthreads', $node['nodeid'], false, $node['parentid']))
							{
								$bbCodeOptions = array();
								foreach($options AS $optionName => $optionVal)
								{
									$bbCodeOptions[$optionName] = (bool)($node['options'] & $optionVal);
								}
							}
							else
							{
								$bbCodeOptions = array();
							}
							$results[$node['nodeid']] =  array(
								'bbcodeoptions' => $bbCodeOptions,
								'rawtext'       => $node['rawtext'],
								'attachments'   => $attachments,
								'channelid'     => $node['channelid'],
								'htmlstate'     => $node['htmlstate']);
						}

					}
					else
					{
						$results[$node['nodeid']] =  array(
							'bbcodeoptions' => array(),
							'rawtext'       => '',
							'attachments'   => array(),
							'channelid'     => $node['channelid'],
							'htmlstate'     => 'off');

					}
				}
				catch (exception $e)
				{
					//probably a permission error. We can continue with whatever is valid.
 				}
			}
		}

		return $results;
	}

	/** Does basic input cleaning for input data
	 	@param	mixed	array of fieldname => data pairs

	 	@return	mixed	the same data after cleaning.
	 */
	public function cleanInput(&$data, $nodeid = false)
	{
		parent::cleanInput($data, $nodeid);

		$canUseHtml = vB::getUserContext()->getChannelPermission('forumpermissions2', 'canusehtml', (empty($nodeid) AND isset($data['parentid'])) ? $data['parentid'] : $nodeid);

		if (isset($data['htmlstate']))
		{
			if ($canUseHtml)
			{
				switch ($data['htmlstate'])
				{
					case 'on':
					case 'on_nl2br':
					case 'off':
						// We're ok, don't do anything.
						break;
					default:
						$data['htmlstate'] = 'off';
						break;
				}
			}
			else
			{
				// User can't use HTML.
				$data['htmlstate'] = 'off';
			}
		}
	}

	public function fixAttachBBCode($nodeId)
	{
		$content = vB_Api::instanceInternal('node')->getNodeFullContent($nodeId);
		$content = $content[$nodeId];
		$oldrawtext = $content['rawtext'];
		if (preg_match_all('#\[attach(?:=(right|left|config))?\]([[:alnum:]]+)\[/attach\]#i', $content['rawtext'], $matches))
		{
			foreach($matches[2] AS $key => $attachmentid)
			{
				$align = $matches[1]["$key"];
				if (preg_match('#^n(\d+)$#', $attachmentid, $matches2))
				{
					// if the id has 'n' as prefix, we need to fix the nodeid
					$attachmentid = intval($matches2[1]);
					if (!empty($content['attachments'][$attachmentid]))
					{
						$attachnodeid = $content['attachments'][$attachmentid]['nodeid'];
						$newattachbbcode = "[ATTACH" . (!empty($align) ? '=' . $align : '') . "]n" . $attachnodeid . "[/ATTACH]";
						$content['rawtext'] = str_replace($matches[0][$key], $newattachbbcode, $content['rawtext']);
					}
				}
			}
		}

		if (preg_match_all('#filedata/fetch\?filedataid=(\d+)#si', $content['rawtext'], $matches))
		{
			foreach($matches[1] AS $key => $attachmentid)
			{
				if (!empty($content['attachments'][$attachmentid]))
				{
					$attachnodeid = $content['attachments'][$attachmentid]['nodeid'];
					$newattachbbcode = "filedata/fetch?id=" . $attachnodeid;
					$content['rawtext'] = str_replace($matches[0][$key], $newattachbbcode, $content['rawtext']);
				}
			}
		}

		if ($oldrawtext != $content['rawtext'])
		{
			$this->update($nodeId, array('parentid' => $content['parentid'], 'rawtext' => $content['rawtext']));
		}

	}

}
