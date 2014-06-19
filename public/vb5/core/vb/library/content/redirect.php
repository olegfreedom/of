<?php
if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/


/**
 * vB_Api_Content_Redirect
 *
 * @package vBApi
 * @author dgrove
 * @copyright Copyright (c) 2011
 * @version $Id$
 * @access public
 */
class vB_Library_Content_Redirect extends vB_Library_Content_Text
{
	//override in client- the text name
	protected $contenttype = 'vBForum_Redirect';

	//The table for the type-specific data.
	protected $tablename = 'redirect';

	//When we parse the page.
	protected $bbcode_parser = false;

	//Whether we change the parent's text count- 1 or zero
	protected $textCountChange = 0;

	//Whether we inherit viewperms from parents
	protected $inheritViewPerms = 1;

	//Does this content show author signature?
	protected $showSignature = false;

	/**
	 * If true, then creating a node of this content type will increment
	 * the user's post count. If false, it will not. Generally, this should be
	 * true for topic starters and replies, and false for everything else.
	 *
	 * @var	bool
	 */
	protected $includeInUserPostCount = false;

	protected $spamType = false;

	protected function __construct()
	{
		parent::__construct();
		$this->spamType = false;
	}

	/**
	 * Creates a new redirect node
	 *
	 * @param	mixed	Array of field => value pairs which define the record.
	 * @param	array	Array of options for the content being created.
	 * @param	bool	Convert text to bbcode
	 *
	 * @return	int	New node id
	 */
	public function add($data, array $options = array(), $convertWysiwygTextToBbcode = false)
	{
		$options['skipFloodCheck'] = true;
		$options['skipDupCheck'] = true;
		$options['skipNotification'] = true;
		$options['skipUpdateLastContent'] = true;

		return parent::add($data, $options, $convertWysiwygTextToBbcode);
	}


	/**
	 * Deletes all expired redirects
	 *
	 */
	public function deleteExpiredRedirects()
	{
		$timenow = vB::getRequest()->getTimeNow();
		$contenttypeid = vB_Types::instance()->getContentTypeId($this->contenttype);
		$assertor =  vB::getDbAssertor();

		$expiredRedirects = $assertor->getRows('vBForum:node', array(
				vB_dB_Query::CONDITIONS_KEY=> array(
					array('field'=>'contenttypeid', 'value' => $contenttypeid, vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_EQ),
					array('field'=>'unpublishdate', 'value' => $timenow, vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_LTE)
				),
			)
		);

		$redirectids = array();
		foreach ($expiredRedirects as $redirect)
		{
			$redirectids[] = $redirect['nodeid'];
		}

		$assertor->delete('vBForum:redirect',
			array(
				array('field' => 'nodeid', 'value' => $redirectids, vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_EQ)
			)
		);

		$assertor->delete('vBForum:node',
			array(
				array('field' => 'nodeid', 'value' => $redirectids, vB_Db_Query::OPERATOR_KEY => vB_Db_Query::OPERATOR_EQ)
			)
		);
	}
	
	/*** Returns the node content as an associative array with fullcontent
	 *	@param	mixed	integer or array of integers=The id in the primary table
	 *	@param array permissions
	 *
	 * 	 *	@param bool	appends to the content the channel routeid and title, and starter route and title the as an associative array
	 ***/
	public function getFullContent($nodes, $permissions = false)
	{
		if (empty($nodes))
		{
			return array();
		}

		$results = parent::getFullContent($nodes, $permissions);
		
		// collect all destiny nodeids and store a reference results
		foreach ($results AS $nodeid => $node)
		{
			$redirect[$node['tonodeid']] =& $results[$nodeid];
		}

		if (!empty($redirect)) //should only happen if data is damaged.
		{
			// fetch destiny nodes info
			$destiny = vB_Library::instance('node')->getFullContentForNodes(array_keys($redirect));
			foreach ($destiny AS $d)
			{
				// this modifies $result
				$redirect[$d['nodeid']]['toNode'] = $d;
			}
		}

		return $results;
	}
}
