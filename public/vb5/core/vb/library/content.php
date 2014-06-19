<?php
if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
   || #################################################################### ||
   || # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
   || # ---------------------------------------------------------------- # ||
   || # Copyright  2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
   || # This file may not be redistributed in whole or significant part. # ||
   || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
   || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
   || #################################################################### ||
   \*======================================================================*/


/**
 * vB_Library_Content
 *
 * @package vBApi
 * @author ebrown
 * @copyright Copyright (c) 2011
 * @version $Id$
 * @access public
 */
abstract class vB_Library_Content extends vB_Library
{
	//override in client- the text name
	protected $contenttype = false;

	//set internally
	protected $contenttypeid;

	//The table for the type-specific data.
	protected $tablename;

	//list of fields that are included in the index
	protected $index_fields = array();

	//Whether we change the parent's text count- 1 or zero
	protected $textCountChange = 0;

	//Whether we inherit viewperms from parents
	protected $inheritViewPerms = 0;

	//Whether this shows on a section listing
	protected $inlist = 1;

	//Does this content show author signature?
	protected $showSignature = false;

	//We need a way to skip flood check for types like Photos, where we'll upload several together.
	protected $doFloodCheck = true;

	protected $assertor;
	protected $nodeApi;
	protected $nodeLibrary;

	/** Whether we are caching node content */
	protected static $cacheNodes;

	protected $channelTypeId;

	protected $cannotDelete = false;

	//This, plus the node fields, are what somebody can see if they don't have canviewthreads in that channel
	protected $allCanview = array('channelroute' => 'channelroute', 'channeltitle' =>  'channeltitle', 'channelid' =>  'channelid',
		'edit_reason' =>  'edit_reason', 'edit_userid' =>  'edit_userid', 'edit_username' =>  'edit_username',
		'edit_dateline' =>  'edit_dateline', 'hashistory' =>  'hashistory',  'starternodeoptions' =>  'starternodeoptions',
		'channelnodeoptions' =>  'channelnodeoptions');

	/**
	 * If true, then creating a node of this content type will increment
	 * the user's post count. If false, it will not. Generally, this should be
	 * true for topic starters and replies, and false for everything else.
	 *
	 * @var	bool
	 */
	protected $includeInUserPostCount = false;

	//Array of nodeoptions according the channeltype
	protected static $defaultNodeOptions = array(
		'forum' => 138,			//vB_Api_Node::OPTION_AUTOAPPROVE_SUBSCRIPTION + //128
								//vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP + // 8
								//vB_Api_Node::OPTION_ALLOW_POST //2

		'blog' => 522,			//vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP + // 8
								//vB_Api_Node::OPTION_AUTOSUBSCRIBE_ON_JOIN // 512;
								//vB_Api_Node::OPTION_ALLOW_POST //2

		'group' => 10,			//vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP + // 8
								//vB_Api_Node::OPTION_ALLOW_POST //2

		'vm' => 138,
		'pm' => 138,
		'album' => 138,
		'report' => 138,
		'infraction' => 138,
		'default' => 138
	);

	//This defines the cache levels.  If the user requests say node Data, and we only have
	//cached content data- pass the data anyway.
	const CACHELEVEL_NODE = 1;
	const CACHELEVEL_CONTENT = 2;
	const CACHELEVEL_FULLCONTENT = 3;

	protected function __construct()
	{
		parent::__construct();
		$this->contenttypeid = vB_Types::instance()->getContentTypeId($this->contenttype);
		//The table for the type-specific data.
		$this->assertor = vB::getDbAssertor();
		$this->nodeApi = vB_Api::instanceInternal('node');
		$this->nodeLibrary = vB_Library::instance('node');
		$this->nodeFields = $this->nodeApi->getNodeFields();
		$this->options = vB::getDatastore()->getValue('options');
		$this->channelTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		$config = vB::getConfig();
		$structure = $this->assertor->fetchTableStructure('vBForum:node');
		foreach($structure['structure'] AS $fieldName)
		{
			$this->allCanview[$fieldName] = $fieldName;
		}
		$structure = $this->assertor->fetchTableStructure('vBForum:channel');
		foreach($structure['structure'] AS $fieldName)
		{
			$this->allCanview[$fieldName] = $fieldName;
		}

		self::$cacheNodes = vB::getDatastore()->getOption('cache_node_data');
	}

	public function fetchContentType()
	{
		return $this->contenttype;
	}


	public function fetchContentTypeId()
	{
		return $this->contenttypeid;
	}

	/**
	 * Returns textCountChange property
	 * @return int
	 */
	public function getTextCountChange()
	{
		return $this->textCountChange;
	}

	/**
	 * Returns inlist property
	 * @return int
	 */
	public function getInlist()
	{
		return $this->inlist;
	}



	/*** Adds a new node.
	 *
	 *	@param	mixed		Array of field => value pairs which define the record.
	 *  @param	array		Array of options for the content being created. See subclasses for more info. skipFloodCheck and skipNotifications are useful
	 *
	 * 	@return	integer		the new nodeid
	 ***/
	public function add($data, array $options = array())
	{
		if (empty($data['parentid']))
		{
			throw new vB_Exception_Api('need_parent_node');
		}

		// *************************************
		// * Fill some default data if missing *
		// *************************************
		if (empty($data['userid']))
		{
			$user = vB::getCurrentSession()->fetch_userinfo();
			$data['authorname'] = $user['username'];
			$userid = $data['userid'] = $user['userid'];
		}
		else
		{
			$userid = $data['userid'];
			if (empty($data['authorname']))
			{
				$data['authorname'] = vB_Api::instanceInternal('user')->fetchUserName($userid);
			}
		}

		if (empty($data['ipaddress']))
		{
			$data['ipaddress'] = vB::getRequest()->getIpAddress();
		}

		$parentInfo = self::fetchFromCache($data['parentid'], self::CACHELEVEL_FULLCONTENT);

		if ($parentInfo AND $parentInfo['found'] AND $parentInfo['found'][$data['parentid']])
		{
			$parentInfo = $parentInfo['found'][$data['parentid']];
		}
		else
		{
			$parentInfo = vB_Library::instance('node')->getNodeContent($data['parentid']);
			$parentInfo = $parentInfo[$data['parentid']];
		}

		//we don't add to a closed channel.
		if ($parentInfo['open'] == 0 AND !vB::getUserContext()->getChannelPermission('moderatorpermissions', 'canmoderateposts', $data['parentid']))
		{
			throw new vB_Exception_Api('invalid_request');
		}

		//We always inherit the parents "protected" value
		if (!isset($data['protected']) || $data['protected'] != 1)
		{
			$data['protected'] = $parentInfo['protected'];
		}

		$channelContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Channel');

		// we cannot add content to category channels
		if (
			$parentInfo['contenttypeid'] == $channelContentTypeId AND $parentInfo['category'] != 0
			AND
			$this->contenttypeid != $channelContentTypeId
		)
		{
			throw new vB_Exception_Api('invalid_request');
		}

		$redirectContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Redirect');

		if ($parentInfo['contenttypeid'] == $redirectContentTypeId)
		{
			throw new vB_Exception_Api('cannot_reply_to_redirect');
		}

		// the starter cannot be a channel, so if this is the case set the starter after adding the node
		//we also need to set the routeid
		if (($this->contenttypeid != $channelContentTypeId) AND ($parentInfo['contenttypeid'] != $channelContentTypeId))
		{
			//The parent already has a conversation route
			if (!empty($parentInfo['starter']))
			{
				$data['starter'] = $parentInfo['starter'];
			}
			else
			{
				//if the parent is a text type but the starter isn't set then we need to get the
				$data['starter'] = $this->getStarter($data['parentid']);
			}
			$data['routeid'] = $parentInfo['routeid'];
		}
		else if ($this->contenttypeid != $channelContentTypeId)
		{
			$route = $this->assertor->getRow('routenew', array('contentid' => $data['parentid'], 'class' => 'vB5_Route_Conversation'));

			if (empty($route) OR !empty($route['errors']))
			{
				throw new vB_Exception_Api('invalid_route_contact_vbulletin_support');
			}

			$data['routeid'] = $route['routeid'];
		}

		// Verify prefixid
		if ($this->contenttypeid != $channelContentTypeId AND !empty($data['prefixid']))
		{
			$this->verifyPrefixid($data['prefixid']);
		}
		else
		{
			// Channel can't have a prefix
			unset($data['prefixid']);
		}

		// Verify post iconid
		if ($this->contenttypeid != $channelContentTypeId AND !empty($data['iconid']))
		{
			$this->verifyPostIconid($data['iconid']);
		}
		else
		{
			// Channels can't have a post icon
			unset($data['iconid']);
		}

		//we generally do a flood check- when was this user's last post?
		if (empty($options['skipFloodCheck']) AND ($this->options['floodchecktime'] > 0) AND ($this->doFloodCheck) AND !vB::getUserContext()->isAdministrator())
		{
			if ($lastPostElapsed = $this->isFlood($data))
			{
				throw new vB_Exception_Api('postfloodcheck', array($this->options['floodchecktime'],
						$this->options['floodchecktime'] - $lastPostElapsed));
			}
		}

		$data['lastupdate'] = vB::getRequest()->getTimeNow();

		if (empty($data['created']))
		{
			$data['created'] = vB::getRequest()->getTimeNow();
		}

		// set publishdate to now...
		if (!isset($data['publishdate']))
		{
			$data['publishdate'] = vB::getRequest()->getTimeNow();
		}

		//It's possible we already have a nodeid.
		$nodevals = array();

		if ($this->isPublished($data))
		{
			$nodevals['showpublished'] = 1;
		}
		else
		{
			$nodevals['showpublished'] = 0;
		}

		// inherit showpublish from the parent
		if (($parentInfo['showpublished'] == 0) AND ($this->contenttype != 'vBForum_PrivateMessage'))
		{
			$nodevals['showpublished'] = 0;
		}

		// inherit viewperms from parent
		if ($this->inheritViewPerms OR !isset($data['viewperms']))
		{
			$nodevals['viewperms'] = $parentInfo['viewperms'];
			unset($data['viewperms']);
		}

		$nodevals['inlist'] = $this->inlist;

		//If this user doesn't have the featured permission and they are trying to set it,
		//Let's just quietly unset it.
		if (isset($data['featured']))
		{
			if (!vB::getUserContext()->getChannelPermission('moderatorpermissions', 'cansetfeatured', $data['parentid']))
			{
				unset($data['featured']);
			}
		}

		if (empty($data['htmltitle']) AND !empty($data['title']))
		{
			$data['htmltitle'] = vB_String::htmlSpecialCharsUni(vB_String::stripTags($data['title']), false);
		}

		if (empty($data['urlident']) AND !empty($data['title']))
		{
			$data['urlident'] = vB_String::getUrlIdent($data['title']);
		}

		// check for accidental duplicate post

		$crc32 = $this->duplicateCheck($data, $options);

		$parentid = $data['parentid'];
		//let's set the appropriate fields in the node table
		foreach ($data as $field => $value)
		{
			if (in_array($field, $this->nodeFields))
			{
				$nodevals[$field] = $value;
				if (!isset($this->contentDupFields) OR !in_array($field, $this->contentDupFields))
				{
					unset($data[$field]);
				}
			}
		}
		if (empty($nodevals))
		{
			throw new vB_Exception_Api('invalid_data');
		}

		if (empty($nodevals['userid']))
		{
			$nodevals['userid'] = vB::getCurrentSession()->get('userid');
		}

		if (empty($this->contenttypeid))
		{
			$this->contenttypeid = vB_Types::instance()->getContentTypeId($this->contenttype);
		}

		$nodevals['contenttypeid'] = $this->contenttypeid;
		$nodevals[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;

		//check for next update needed
		if (!empty($nodevals['publishdate']) AND !empty($nodevals['unpublishdate']))
		{
			$nodevals['nextupdate'] = min($nodevals['publishdate'], $nodevals['unpublishdate']);
		}
		else if (!empty($nodevals['unpublishdate']))
		{
			$nodevals['nextupdate'] = $nodevals['unpublishdate'];
		}
		else
		{
			$nodevals['nextupdate'] = 0;
		}

		//We need the correct nodeoptions setting. If this is not a channel we should inherit.
		$parentFullContent = vB_Library::instance('node')->getNodeFullContent($nodevals['parentid']);
		if ($this->contenttype == 'vBForum_Channel')
		{
			if (empty($nodevals['nodeoptions']) OR !is_numeric($nodevals['nodeoptions']))
			{
				if(!empty($parentFullContent[$nodevals['parentid']]['channeltype']))
				{
					$nodevals['nodeoptions'] = self::$defaultNodeOptions[$parentFullContent[$nodevals['parentid']]['channeltype']];
				}
				else
				{
					$nodevals['nodeoptions'] = self::$defaultNodeOptions['default'];
				}

				if($parentFullContent[$nodevals['parentid']]['channeltype'] == 'blog' AND isset($data['enable_comments']))
				{
					if ($data['enable_comments'])
					{
						$nodevals['nodeoptions'] = $nodevals['nodeoptions'] | vB_Api_Node::OPTION_ALLOW_POST;
					}
					else
					{
						$nodevals['nodeoptions'] = $nodevals['nodeoptions'] ^ vB_Api_Node::OPTION_ALLOW_POST;
					}
				}
			}
		}
		else //if is not blog, we inherit from the parent.
		{
			if($parentFullContent[$nodevals['parentid']]['channeltype'] == 'blog')
			{
				$nodevals['nodeoptions'] = self::$defaultNodeOptions[$parentFullContent[$nodevals['parentid']]['channeltype']];
				if(isset($data['enable_comments']))
				{
					if ($data['enable_comments'])
					{
						$nodevals['nodeoptions'] = $nodevals['nodeoptions'] | vB_Api_Node::OPTION_ALLOW_POST;
					}
					else
					{
						$nodevals['nodeoptions'] = $nodevals['nodeoptions'] ^ vB_Api_Node::OPTION_ALLOW_POST;
					}
				}
			}
			else
			{
				$nodevals['nodeoptions'] = $parentFullContent[$nodevals['parentid']]['nodeoptions'];
			}
		}

		$vmParentid = vB_Api::instanceInternal('node')->fetchVMChannel();
		if ($vmParentid == $parentInfo['nodeid'])
		{
			if (
				!vB::getUserContext()->hasPermission('visitormessagepermissions', 'followforummoderation')
					AND
				$nodevals['setfor'] != $nodevals['userid']
			)
			{
				$nodevals['approved'] = 0;
				$nodevals['showapproved'] = 0;
			}

			if (!intval($nodevals['setfor']))
			{
				throw new vB_Exception_Api('invalid_data');
			}
		}

		try
		{
			if (empty($options['skipTransaction']))
			{
				$this->assertor->beginTransaction();
			}
			$nodeid = $this->assertor->assertQuery('vBForum:node', $nodevals);

			if (!$nodeid)
			{
				$this->assertor->rollbackTransaction();
				throw new vB_Exception_Api('invalid_data');
			}

			if (is_array($nodeid))
			{
				$nodeid = $nodeid[0];
			}

			//Let's set the lastcontent and lastcontentid values
			if ($this->contenttype != 'vBForum_Channel')
			{
				vB::getDbAssertor()->assertQuery('vBForum:node', array(
					vB_dB_Query::TYPE_KEY=> vB_dB_Query::QUERY_UPDATE,
					'nodeid' => $nodeid,
					'lastcontent' =>  vB::getRequest()->getTimeNow(),
					'lastcontentid' => $nodeid,
					'lastcontentauthor' => $nodevals['authorname'],
					'lastauthorid' => $nodevals['userid'],
				));
			}

			$cacheEvents = array("fUserContentChg_" . $nodevals['userid'], 'userChg_' . $userid);
			if ($this->contenttypeid == $channelContentTypeId)
			{
				$cacheEvents[] = "nodeChg_" . $nodevals['parentid'] ;
			}
			else
			{
				$channelNodeid = 0;
				if (!isset($nodevals['starter']))
				{
					//The only reason this would be unset is that THIS is the starter.
					$update = array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
						'starter' => $nodeid, 'nodeid' => $nodeid);
					$this->assertor->assertQuery('vBForum:node', $update);
					//Since this is the starter, the parent is the channel.
					$cacheEvents[] = "nodeChg_" . $parentid;
				}
				else
				{
					//we need cache events to for the starter, which we have, and the
					//channel, which we don't. But we know it's the parent of the starter.
					$starterNodeInfo = vB_Library::instance('node')->getNodeBare($nodevals['starter']);
					$cacheEvents[] = "nodeChg_" . $nodevals['starter'];
					$cacheEvents[] = "nodeChg_" . $starterNodeInfo['parentid'] ;
					if ($nodevals['starter'] != $parentid)
					{
						$cacheEvents[] = "nodeChg_" . $parentid;
					}
					$cacheEvents[] = "fUserContentChg_" . $starterNodeInfo['userid'];
					$channelNodeid = $starterNodeInfo['parentid'];
				}

				// Check "Moderate comments before displaying"
				// If !$channelNodeid, the node is a starter not a comment so we skip it.
				if ($channelNodeid)
				{
					$channel = vB_Library::instance('node')->getNode($channelNodeid);
					// If it's the owner of channel who posted the comment, we need to approve it
					// TODO: we may need to allow Admin and moderator to bypass the limit?
					if ($nodevals['userid'] != $channel['userid'] AND $channel['moderate_comments'])
					{
						$this->assertor->assertQuery('vBForum:node', array(
							vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
							'approved' => 0,
							'showapproved' => 0,
							vB_dB_Query::CONDITIONS_KEY => array(
								'nodeid' => $nodeid,
							)
						));
					}
				}
			}
			vB_Cache::instance()->allCacheEvent($cacheEvents);
			//Now update the closure table.
			$this->assertor->assertQuery('vBForum:closure', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
				'parent' => $nodeid,
				'child' => $nodeid,
				'depth' => 0,
				'publishdate' => $nodevals['publishdate'],
			));

			$this->assertor->assertQuery('vBForum:addClosure', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'nodeid' => $nodeid,
			));

			$this->updateNodeOptions($nodeid, $data);

			// Clear autosave table of this items entry
			if (vB::getCurrentSession()->get('userid')
				AND
				!empty($data['rawtext'])
			)
			{
				$this->assertor->delete('vBForum:autosavetext', array(
					'userid'   => vB::getCurrentSession()->get('userid'),
					'nodeid'   => 0,
					'parentid' => $parentid
				));
			}

			if (!empty($nodevals['nextupdate']))
			{
				$nodevals['nodeid'] = $nodeid;
				$this->assertor->assertQuery('vBForum:setNextUpdate', array(
					'nodeid' => $nodeid,
					'publishdate' => $nodevals['publishdate'],
					'unpublishdate'	=> (isset($nodevals['unpublishdate']) ? $nodevals['unpublishdate'] : 0),
				));
			}

			//handle the 'index' setting;
			$index = empty($data['noIndex']);
			unset($data['noIndex']);

			// Insert the content-type specific data
			if (!is_array($this->tablename))
			{
				$tables = array($this->tablename);
			}
			else
			{
				$tables = $this->tablename;
			}

			foreach ($tables as $table)
			{
				$structure = $this->assertor->fetchTableStructure('vBForum:' . $table);
				$queryData = array();
				$queryData[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_INSERT;
				$queryData['nodeid'] = $nodeid;
				foreach ($structure['structure'] AS $fieldname)
				{
					if (isset($data[$fieldname]))
					{
						$queryData[$fieldname] = $data[$fieldname];
					}
				}
				$this->assertor->assertQuery('vBForum:' . $table, $queryData);
			}

			//If published and if this is a text class we should update the text counts.
			if ($textCountChange = $this->textCountChange)
			{
				if ($nodevals['showpublished'])
				{
					$this->updateParentCounts($nodeid, $textCountChange, 0, $textCountChange, 0, 1, (!isset($options['skipUpdateLastContent']) OR !$options['skipUpdateLastContent']));
				}
				else
				{
					$this->updateParentCounts($nodeid, 0, $textCountChange, 0, $textCountChange, 0, (!isset($options['skipUpdateLastContent']) OR !$options['skipUpdateLastContent']));
				}
			}

			if (empty($options['skipTransaction']))
			{
				$this->assertor->commitTransaction();
			}
		}
		catch (exception $e)
		{
			//Catch the transaction.
			if (empty($options['skipTransaction']))
			{
				$this->assertor->rollbackTransaction();
			}
			throw $e;
		}


		if ($index)
		{
			vB_Api::instanceInternal('Search')->index($nodeid);
		}
		if (!empty($crc32))
		{
			$this->assertor->insert('vBForum:nodehash', array(
				'nodeid' => $nodeid,
				'dupehash' => $crc32,
				'userid' => $userid,
				'dateline' => vB::getRequest()->getTimeNow()
			));
		}
		vB_Api::instanceInternal('Search')->purgeCacheForCurrentUser();

		//force a reload.
		vB_Cache::allCacheEvent("nodeChg_$nodeid");
		$node = vB_Library::instance('node')->getNode($nodeid);

		if ($this->isVisitorMessage($nodeid))
		{
			if (!empty($node['setfor']))
			{
				vB_Search_Core::instance()->purgeCacheForUser($node['setfor']);
			}
		}
		vB_Cache::instance()->allCacheEvent("fContentChg_$parentid");

		// update tags
		if (!empty($data['tags']))
		{
			$tagRet = vB_Api::instanceInternal('tags')->addTags($nodeid, $data['tags']);
		}

		//Let's see if we need to send notifications
		// first we need to get the current node.
		//Private messages are different. Let the subclass handle them.
		$notifications = array();
		if (($this->contenttype != 'vBForum_PrivateMessage') AND empty($options['skipNotification']))
		{
			$node = vB_Library::instance('node')->getNode($nodeid);
			//If this is a visitor message we always send a message
			// we have the $node from above
			if ($this->isVisitorMessage($nodeid) AND !empty($node['setfor']))
			{
				$notifications[] = array(
					'about' => vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_VM,
					'aboutid' => $node['nodeid'],
					'userid' => $node['setfor'],
					'contentnodeid' => $nodeid,
				);
			}
			else if (($node['starter'] > 0) AND ($node['starter'] != $nodeid))
			{
				//See if we need to send moderation messages.
				if (empty($node['approved']))
				{
					$starter = vB_Library::instance('node')->getNode($node['starter']);
					$moderators = vB_Api::instanceInternal('blog')->fetchContributors($starter['parentid']);
					if ($moderator = vB_Api::instanceInternal('blog')->fetchOwner($starter['parentid']))
					{
						$moderators[] = $moderator;
					}
					foreach (array_unique($moderators) AS $moderator)
					{
						$notifications[] = array('about' => vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_MODERATE,
							'aboutid' => $nodeid, 'userid' => $moderator);
					}
				}

				//If this is in response to the starter, it's a reply.
				$starter = vB_Library::instance('node')->getNode($node['starter']);
				if ($node['starter'] == $node['parentid'])
				{
					$notifications[] = array('about' => vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_REPLY,
						'aboutid' => $node['nodeid'], 'userid' => $starter['userid'], 'contentnodeid' => $nodeid);
				}
				else
				{
					// If it's not a reply, then it's a comment
					// Reply poster who gets commented gets a COMMENT notification.
					// aboutid is the nodeid of the reply that was commented
					$parent = vB_Library::instance('node')->getNode($nodevals['parentid']);
					$notifications[] = array('about' => vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_COMMENT,
						'aboutid' => $node['parentid'], 'userid' => $parent['userid'], 'contentnodeid' => $nodeid);

					// topic creator (and subscribers when implemented) will get a THREADCOMMENT notification to let them
					// know that the topic has new content
					$notifications[] = array('about' => vB_Library_Content_Privatemessage::NOTIFICATION_TYPE_THREADCOMMENT,
						'aboutid' => $node['parentid'] , 'userid' => $starter['userid'], 'contentnodeid' => $nodeid);
				}
			}
		}
		// @TODO VBV-2303 we can't send notifications as guest... private message library do checkFolders() which won't be valid for guests.
		if (!empty($notifications) AND empty($options['skipNotifications']) AND intval(vB::getCurrentSession()->get('userid')))
		{
			$this->sendNotifications($notifications);
		}
		// Update the post count for this user (content add)
		$this->incrementUserPostCount($node);

		vB_Library::instance('node')->clearCacheEvents($parentid);

		//For the children
		return $nodeid;
	}

	protected function isFlood($data)
	{
		$isFlood = false;

		$node = vB::getDbAssertor()->getRow('vBForum:node',
			array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				vB_dB_Query::CONDITIONS_KEY => array(
					array('field' => 'userid', 'value' => $data['userid'], 'operator' => vB_dB_Query::OPERATOR_EQ),
					array('field' => 'created', 'value' =>  vB::getRequest()->getTimeNow() - $this->options['floodchecktime'], 'operator' => vB_dB_Query::OPERATOR_GT),
					array('field' => 'contenttypeid', 'value' => $this->contenttypeid, 'operator' => vB_dB_Query::OPERATOR_EQ),
				)
			),
			array('field' => array('created'), 'direction' => array(vB_dB_Query::SORT_DESC))
		);

		if (!empty($node))
		{
			$lastPostElapsed = vB::getRequest()->getTimeNow() - $node['created'];
			if ($lastPostElapsed < $this->options['floodchecktime'])
			{
				$isFlood = true;
			}
			vB_Library_Content::writeToCache(array($node), vB_Library_Content::CACHELEVEL_NODE);
		}

		if ($isFlood)
		{
			return $lastPostElapsed;
		}
		return false;
	}

	/**
	 * Checks accidental duplicate posting
	 * @param array $data
	 * @param array $options - optional
	 * @return string CRC32 or boolean
	 */
	protected function duplicateCheck($data, $options = array())
	{
		if (!empty($options['skipDupCheck']))
		{
			return false;
		}
		$crc32 = $this->getCRC32($data);

		if ((!defined('VB_AREA') OR VB_AREA != 'Upgrade')
			AND (!defined('VB_TEST'))
			AND !empty($crc32) AND $this->assertor->getRow('vBForum:nodehash', array(vB_dB_Query::CONDITIONS_KEY => array(
				array('field' => 'dupehash', 'value' => $crc32, 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'userid', 'value' => $data['userid'], 'operator' => vB_dB_Query::OPERATOR_EQ),
				array('field' => 'dateline', 'value' => vB::getRequest()->getTimeNow() - 300, 'operator' => vB_dB_Query::OPERATOR_GT) // less than 5 minutes
		))))
		{
			throw new vB_Exception_Api('duplicate_post');
		}
		return $crc32;
	}
	/** Sets node options from individual fields.
	 *
	 * 	@param	int
	 * 	@param	mixed	array of field => value pairs. Both must be strings, but non-matching keys will be ignored.
	 *
	 */
	protected function updateNodeOptions($nodeid, $data)
	{
		//set noteoptions if we got them.
		if (!isset($data['nodeoptions']))
		{
			$nodeOptions = vB_Api::instanceInternal('node')->getOptions();
			$options = array();
			foreach ($nodeOptions AS $optionKey => $optionVal)
			{

				if (isset($data[$optionKey]))
				{
					$options[$optionKey] = $data[$optionKey];
				}
			}

			if (!empty($options))
			{
				vB_Library::instance('node')->setNodeOptions($nodeid, $options);
			}
		}
		else
		{
			vB_Library::instance('node')->setNodeOptions($nodeid, $data['nodeoptions']);
		}

	}

	/** Updates the parent counts and data when saving a node.
	 *
	 *	@param	mixed	node record
	 *
	 ***/
	protected function updateParentCounts($nodeid, $textChange, $textUnPubChange, $totalPubChange, $totalUnPubChange, $published, $updatelastcontent = true)
	{
		$parents = vB_Library::instance('node')->getParents($nodeid);
		$parentids = array();
		//The first record will be the node itself.
		foreach ($parents AS $parent)
		{
			if ($parent['nodeid'] == $nodeid)
			{
				continue;
			}
			$parentids[] = $parent['nodeid'];
		}

		$parentids = array_unique($parentids);

		if (!empty($parentids))
		{
			$this->assertor->assertQuery('vBForum:UpdateParentCount',
			array(vB_dB_Query::TYPE_KEY =>vB_dB_Query::QUERY_STORED,
			'nodeid' => $parentids[0],
			'textChange' => $textChange, 'textUnpubChange' => $textUnPubChange));

			$this->assertor->assertQuery('vBForum:UpdateAncestorCount',
			array(vB_dB_Query::TYPE_KEY =>vB_dB_Query::QUERY_STORED,
			'nodeid' => $parentids,
			'totalChange' => $totalPubChange, 'totalUnpubChange' => $totalUnPubChange));

			$nodelib = vB_Library::instance('node');
			$nodelib->clearCacheEvents($parentids);
			if ($published AND $updatelastcontent)
			{
				$searchAPI = vB_Api::instanceInternal('search');
				foreach ($parentids AS $parentid)
				{
					$this->assertor->assertQuery('vBForum:updateLastData', array('parentid' => $parentid, 'timenow' => vB::getRequest()->getTimeNow()));
					$searchAPI->attributeChanged($parentid);
				}
			}
		}
	}

	/**
	 * Increments the number of posts for a user. This function will also update user's "lastpost" field
	 *
	 * When creating items, this is called from the content library,
	 * For all other state changes (approve, undelete, etc) it is called from the node library
	 *
	 * @param	array	Array of node information for the affected node
	 */
	public function incrementUserPostCount(array $node)
	{
		// Avoid incrementing for the same node more than once
		if (isset($this->nodeAddedToPostCount[$node['nodeid']]))
		{
			return;
		}
		$this->nodeAddedToPostCount[$node['nodeid']] = true;

		// Increment for any descendant nodes
		if ($this->shouldChangeUserPostCountForChildNodes($node))
		{
			$children = vB::getDbAssertor()->getRows('vBForum:closure', array('parent' => $node['nodeid']));

			if (!empty($children))
			{
				foreach ($children AS $child)
				{
					vB_Cache::allCacheEvent('nodeChg_' . $child['child']);
					$nodeInfo = vB_Api::instance('node')->getNode($child['child']);

					if (!empty($nodeInfo) AND empty($nodeInfo['errors']))
					{
						$this->incrementUserPostCount($nodeInfo);
					}
				}
			}
		}

		if (!$this->countInUserPostCount($node))
		{
			return;
		}

		$this->assertor->assertQuery('vBForum:incrementUserPostCount', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'userid' => $node['userid'],
			'timenow' => vB::getRequest()->getTimeNow(),
			'lastnodeid' => $node['nodeid'],
		));

		vB_Cache::allCacheEvent('userChg_' . $node['userid']);
	}

	/**
	 * Decrements the number of posts for a user
	 *
	 * When hard-deleting items, this is called from the content library,
	 * For all other state changes (unapprove, soft-delete, etc) it is called from the node library
	 *
	 * @param	array	Array of node information for the affected node
	 * @param	(unpublish|unapprove)	Pass one of these strings when decrementUserPostCount is called *after* unpublishing or unapproving a post
	 */
	public function decrementUserPostCount(array $node, $action = '')
	{
		// Avoid decrementing for the same node more than once
		if (isset($this->nodeRemovedFromPostCount[$node['nodeid']]))
		{
			return;
		}
		$this->nodeRemovedFromPostCount[$node['nodeid']] = true;

		// Decrement for any descendant nodes
		if ($this->shouldChangeUserPostCountForChildNodes($node, $action))
		{
			$children = vB::getDbAssertor()->getRows('vBForum:closure', array('parent' => $node['nodeid']));

			if (!empty($children))
			{
				foreach ($children AS $child)
				{
					vB_Cache::allCacheEvent('nodeChg_' . $child['child']);
					$nodeInfo = vB_Api::instance('node')->getNode($child['child']);
					// Don't pass action since the child nodes themselves were not modified
					if (!empty($nodeInfo) AND empty($nodeInfo['errors']))
					{
						$this->decrementUserPostCount($nodeInfo);
					}
				}
			}
		}

		if (!$this->countInUserPostCount($node, $action))
		{
			return;
		}

		$this->assertor->assertQuery('vBForum:decrementUserPostCount', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
			'userid' => $node['userid'],
		));

		vB_Cache::allCacheEvent('userChg_' . $node['userid']);
	}

	/** Reset the called array- this makes sure we only increment/decrement user post counts once for a given node
	 * This is only needed by the unit tests.
	 */
	public function resetCountCalled()
	{
		$this->nodeAddedToPostCount = array();
		$this->nodeRemovedFromPostCount = array();
	}
	/**
	 * Checks if the current node should be counted in the user post count for the author.
	 *
	 * @param	array	The node
	 * @param	(unpublish|unapprove)	The action that was just carried out on the node
	 *
	 * @return	boolean	Whether or not the node should be counted in user post count.
	 */
	protected function countInUserPostCount(array $node, $action = '')
	{
		// NOTICE: How we determine if a post counts in user post count here needs to
		// match the criteria used in admincp/misc.php?do=updateposts
		// If you update in one place, please update in the other


		// check if this content type counts as a "post"
		// We have to jump through a bunch of hoops to not count VMs, PMs,
		// reports, and other items in user post count
		// @todo - VMs and comments should probably be their own content types
		// extended from the Text content type.

		if (!$this->includeInUserPostCount OR $this->isVisitorMessage($node['nodeid']))
		{
			return false;
		}

		if (!isset($node['starter']))
		{
			//force a reload
			vB_Cache::allCacheEvent('nodeChg_'. $node['nodeid']);
			$node = vB_Library::instance('node')->getNodeFullContent($node['nodeid'], $node['contenttypeid']);
		}
		return (!$this->isComment($node['nodeid'], $node)
			AND
			(
				($action == 'unapprove')
					OR
					$node['approved']
			)
			AND
			(
				($action == 'unpublish')
					OR
					(
						$node['showpublished']
							AND
							!$node['deleteuserid']
					)
			)
		);
	}
	/**
	 * Checks whether or not we should adjust user post count for descendant nodes
	 *
	 * @param	array	The node
	 * @param	(unpublish|unapprove)	The action that was just carried out on the node
	 *
	 * @return	boolean	Whether or not the child nodes should be handled
	 */
	protected function shouldChangeUserPostCountForChildNodes($node, $action = '')
	{
		// We don't want to do anything for child nodes if the parent node
		// is currently soft-deleted or unapproved because the child nodes
		// are already not counted in user post count

/*
	=====================================\n
	shouldChangeUserPostCountForChildNodes\n
	\$node[nodeid]: $node[nodeid]\n
	\$node[approved]: $node[approved]\n
	\$node[showapproved]: $node[showapproved]\n
	\$node[showpublished]: $node[showpublished]\n
	\$node[deleteuserid]: $node[deleteuserid]\n
";*/

		return (
			// if the node is approved (or we are unapproving it)
			(
				($action == 'unapprove')
				OR
				(
					$node['approved']
					/*AND
					$node['showapproved']*/
				)
			)
			AND
			// and the node is not soft-deleted (or we are soft-deleting it)
			(
				($action == 'unpublish')
				OR
				(
					$node['showpublished']
					AND
					!$node['deleteuserid']
				)
			)
		);
	}

	/*** Permanently deletes a node
	 *	@param	mixed	The nodeid of the record to be deleted, or an array of nodeids
	 *
	 *	@return	boolean
	 ***/
	public function delete($nodeids)
	{
		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}
		$nodeLib = vB_Library::instance('node');
		$prior = $nodeLib->getNodes($nodeids);
		//Confirm we can delete. This is not a permissions check but a data integrity check, which is why it's in the library.
		foreach ($prior AS $node)
		{
			if ($node['protected'])
			{
				//O.K. if it's not a channel.
				if ($node['contenttypeid'] == vB_Types::instance()->getContentTypeId('vBForum_Channel'))
				{
					throw new vB_Exception_Api('no_delete_permissions');
				}
			}
		}
		$cachedUserIds = array();
		$textChangeTypes = vB_Library::instance('node')->getTextChangeTypes();
		//We need to loop and do these one at a time. If an early node is a child of a later one, the counts will change
		//during the loop. If the early node is a parent of a later node, the node will have been  deleted.

		$events = array();
		foreach ($nodeids AS $nodeid)
		{
			try
			{
				$existing = $nodeLib->getNodeBare($nodeid);
			}
			catch(exception $e)
			{
				continue;
			}

			//This can happen if either the node is invalid or has been deleted
			if (empty($existing) OR !empty($existing['errors']))
			{
				continue;
			}

			// Update user post count (content hard-delete)
			try
			{
				$this->decrementUserPostCount($existing);
			}
			catch(exception $e)
			{
				//nothing to do here- just continue;
			}

			vB_Api::instanceInternal('Search')->delete($nodeid);
			$childTables = array('node');
			$childTypes = $this->assertor->assertQuery('vBForum:getChildContentTypes', array('nodeid' => $nodeid));

			foreach ($childTypes as $childType)
			{
				$childApi = self::getContentApi($childType['contenttypeid']);
				foreach ($childApi->fetchTableName() AS $table)
				{
					if (!in_array($table, $childTables))
					{
						$childTables[] = $table;
					}
				}
			}
			foreach ($childTables AS $childTable)
			{
				$this->assertor->assertQuery('vBForum:deleteChildContentTableRecords', array('nodeid' => $nodeid, 'tablename' => $childTable));
			}

			$events = array_merge($events, array($existing['nodeid'], $existing['parentid'], $existing['starter']));

			$children = $nodeLib->fetchClosurechildren($nodeid);

			if (!empty($children) AND !empty($children[$nodeid]))
			{
				foreach ($children[$nodeid] AS $child)
				{
					$events[] = $child['child'];
				}
			}

			$this->assertor->assertQuery('vBForum:closure',
				array(vB_dB_Query::TYPE_KEY =>  vB_dB_Query::QUERY_DELETE, 'parent' => $nodeid));


			$cachedUserIds[] =  $existing['userid'];
		}

		$nodeLib->clearCacheEvents(array_unique($events));
		$cacheEvents = array();
		foreach (array_unique($cachedUserIds) AS $userid)
		{
			$cacheEvents[] = 'userChg_' . $userid;
		}
		vB_Cache::instance()->allCacheEvent($cacheEvents);
		return true;
	}

	/**
	 * Delete the records without updating the parent info. It is used when deleting a whole channel and it's children need to be removed
	 * @param array $childrenIds - list of node ids
	 */
	public function deleteChildren($childrenIds)
	{
		$specific_tables = $this->fetchTableName();
		$specific_tables[] = 'node';
		foreach ($specific_tables as $table)
		{
			$this->assertor->delete('vBForum:' . $table, array('nodeid' => $childrenIds));
		}
	}

	/*** Is this record in a published state based on the times?
	 *
	 *	@param	mixed
	 *
	 *	@return	bool
	 ****/
	public function isPublished($data)
	{
		if (empty($data['publishdate']))
		{
			return false;
		}
		$timeNow = vB::getRequest()->getTimeNow();

		return ($data['publishdate'] > 0) AND
		($data['publishdate'] <= $timeNow) AND
		(empty($data['unpublishdate']) OR ($data['unpublishdate'] <= 0) OR ($data['unpublishdate'] >= $timeNow) OR
		($data['unpublishdate'] < $data['publishdate']));
	}

	/*** Is this record in approved state?
	*	A node record is considered unapproved either directly (approved field) or indirectly (show approved field).
	*
	*	@param	mixed 	Node record information.
	*
	*	@return	bool 	Whether record is approved or not.
	****/
	public function isApproved($data)
	{
		return (!empty($data['showapproved']) ? true : false);
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
		$channelContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Channel');

		// Verify prefixid
		if ($this->contenttypeid != $channelContentTypeId AND isset($data['prefixid']))
		{
			$this->verifyPrefixid($data['prefixid']);
		}
		else
		{
			// Channel can't have a prefix
			unset($data['prefixid']);
		}

		// Verify post iconid
		if ($this->contenttypeid != $channelContentTypeId AND isset($data['iconid']))
		{
			$this->verifyPostIconid($data['iconid']);
		}
		else
		{
			// Channels can't have a post icon
			unset($data['iconid']);
		}

		$timeNow = vB::getRequest()->getTimeNow();

		//If this user doesn't have the featured permission and they are trying to set it,
		//Let's just quietly unset it.
		if (isset($data['featured']))
		{
			if (!vB::getUserContext()->getChannelPermission('moderatorpermissions', 'cansetfeatured', $data['parentid']))
			{
				unset($data['featured']);
			}
		}

		//We can't allow directly setting parentid. That should only happen through the node api move function
		//And there are number of other fields that shouldn't be changed here. We have methods for the ones that can be changed at all.
		foreach (array('open', 'showopen', 'approved', 'showapproved', 'protected', 'parentid') as $field)
		{
			if (isset($data[$field]))
			{
				unset($data[$field]);
			}
		}


		//We need to see if we need to update.
		$prior = vB_Library::instance('node')->getNodeBare($nodeid);
		if ($this->contenttypeid != $channelContentTypeId)
		{
			$content = $this->getFullContent($nodeid);
		}
		if (($prior['nextupdate'] > $prior['lastupdate']) AND ($prior['nextupdate'] <= $timeNow))
		{
			$this->timedCountUpdates($nodeid);
		}

		if (!isset($data['publishdate']))
		{
			if (!empty($prior['publishdate']))
			{
				$data['publishdate'] = $prior['publishdate'];
			}
			else
			{
				$data['publishdate'] = 0;
			}
		}

		if (!isset($data['unpublishdate']))
		{
			$data['unpublishdate'] = $prior['unpublishdate'];
		}

		if (empty($data['htmltitle']) AND !empty($data['title']))
		{
			$data['htmltitle'] = vB_String::htmlSpecialCharsUni(vB_String::stripTags($data['title']), false);
		}

		if (empty($data['urlident']) AND !empty($data['title']))
		{
			$data['urlident'] = vB_String::getUrlIdent($data['title']);
		}

		if ($this->isPublished($data))
		{
			$published = 1;
		}
		else
		{
			$published = 0;
		}

		$nodevals = array();
		$updateParents = false;
		//We need to compare the current publishdate and unpublishdate values against the
		// parent.
		//But we can skip this if neither publish or unpublishdate is set
		if ($published <> $prior['showpublished'])
		{
			$nodevals['showpublished'] = $published;
			$updateParents = true;
			//We are concerned about two possibilities. It could have gone from published to unpublished.
			//In either case we change by totalcount +1 (for ourselves.
			//Remember that published is always unpublished.

			//From unpublished to published.
			if ($published)
			{
				$textChange = $this->textCountChange;
				$textUnPubChange = -1 * $textChange;
				$totalPubChange = $prior['totalcount'] + $textChange;
				$totalUnPubChange = -1 * $totalPubChange;
			}
			//or from published to unpublished.
			else
			{
				$textChange = -1 * $this->textCountChange;
				$textUnPubChange = $this->textCountChange;
				$totalUnPubChange = $prior['totalcount'] + $this->textCountChange;
				$totalPubChange = -1 * $totalUnPubChange;
			}
		}

		if ((empty($data['nodeoptions']) OR !is_numeric($data['nodeoptions'])) AND $prior['contenttypeid'] != $channelContentTypeId)
		{
			$parentFullContent = vB_Library::instance('node')->getNodeFullContent($prior['parentid']);
			if(!empty($parentFullContent[$prior['parentid']]['channeltype']))
			{
				$data['nodeoptions'] = self::$defaultNodeOptions[$parentFullContent[$prior['parentid']]['channeltype']];
			}
			else
			{
				$data['nodeoptions'] = self::$defaultNodeOptions['default'];
			}

			if(
				$parentFullContent[$prior['parentid']]['channeltype'] == 'blog' AND
				!empty($data['enable_comments']) AND
				!($data['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST)
			)
			{
				$data['nodeoptions'] += vB_Api_Node::OPTION_ALLOW_POST;
			}
		}

		//node table data.
		$data[vB_dB_Query::TYPE_KEY] =  vB_dB_Query::QUERY_UPDATE;
		$data['nodeid'] = $nodeid;
		$data['lastupdate'] =  $timeNow;
		//If the field passed is in the $nodeFields array then we update the node table.
		foreach ($data as $field => $value)
		{
			if (in_array($field, $this->nodeFields))
			{
				$nodevals[$field] = $value;
			}
		}

		$index = empty($data['noIndex']);
		unset($data['noIndex']);

		// Update the content-type specific data
		if (!is_array($this->tablename))
		{
			$tables = array($this->tablename);
		}
		else
		{
			$tables = $this->tablename;
		}

		$success = true;

		foreach ($tables as $table)
		{
			$structure = $this->assertor->fetchTableStructure('vBForum:' . $table);
			if (empty($structure) OR empty($structure['structure']))
			{
				throw new vB_Exception_Api('invalid_query_parameters');
			}
			$queryData = array();
			$queryData[vB_dB_Query::TYPE_KEY] = vB_dB_Query::QUERY_UPDATE;
			$queryData['nodeid'] = $nodeid;
			foreach ($structure['structure'] as $fieldname)
			{
				if (isset($data[$fieldname]))
				{
					$queryData[$fieldname] = $data[$fieldname];
				}
			}
			//Now we have at least a query type and a nodeid. We put those in above. So if we don't
			//have at least one other value there's no reason to try an update.
			if (count($queryData)> 2)
			{
				$success = $success AND $this->assertor->assertQuery('vBForum:' . $table, $queryData);
			}
		}

		if (isset($nodevals['publishdate']) OR isset($nodevals['unpublishdate']))
		{
			$nodevals['lastupdate'] = $timeNow;
			$nodevals[vB_dB_Query::TYPE_KEY] =  vB_dB_Query::QUERY_METHOD;
			$nodevals['nodeid'] = $nodeid;
			$this->assertor->assertQuery('vBForum:setNextUpdate', $nodevals);
			$this->assertor->assertQuery('vBForum:closure', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
				vB_dB_Query::CONDITIONS_KEY => array('child' => $nodeid), 'publishdate' => $nodevals['publishdate']));
		}

		if ($success)
		{
			//Clear cached query info that would be significantly impacted
			$events = array('fUserContentChg_' . $prior['userid']);
			if ($prior['starter'])
			{
				$starterNodeInfo = vB_Library::instance('node')->getNodeBare($prior['starter']);
				$events[] = 'fUserContentChg_' . $starterNodeInfo['userid'];
			}
			else if ($prior['parentid'])
			{
				$starterNodeInfo = vB_Library::instance('node')->getNodeBare($prior['parentid']);
				$events[] = 'fUserContentChg_' . $starterNodeInfo['userid'];
			}

			$this->nodeApi->clearCacheEvents($nodeid);
			vB_Cache::instance()->allCacheEvent($events);

			if (isset($nodevals['publishdate']) OR isset($nodevals['unpublishdate']))
			{
				if (isset($nodevals['publishdate']))
				{
					if (isset($nodevals['unpublishdate']))
					{
						$this->assertor->assertQuery('vBForum:node',
						array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,'nodeid' => $nodeid, 'publishdate' => $nodevals['publishdate'],
						'unpublishdate' =>$nodevals['unpublishdate']));
						unset($nodevals['unpublishdate']);
					}
					else
					{
						$this->assertor->assertQuery('vBForum:node',
						array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,'nodeid' => $nodeid, 'publishdate' => $nodevals['publishdate']));
					}
					unset($nodevals['publishdate']);
				}
				else
				{
					$this->assertor->assertQuery('vBForum:node',
					array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,'nodeid' => $nodeid, 'unpublishdate' => $nodevals['unpublishdate']));
					unset($nodevals['unpublishdate']);
				}
			}

			// handle approved
			if (isset($nodevals['approved']))
			{
				if ($nodevals['approved'])
				{
					$approved = 1;
					$queryName = 'approveNode';
				}
				else
				{
					$approved = 0;
					$queryName = 'unapproveNode';
				}

				// set approved to parent...
				$this->assertor->assertQuery('vBForum:node',
					array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,'nodeid' => $nodeid, 'approved' => $approved
					)
				);

				// and handle showapproved
				$this->assertor->assertQuery('vBForum:' . $queryName, array('nodeid' => $nodeid));
				unset($nodevals['approved']);
			}

			if (isset($nodevals))
			{
				$nodevals[vB_dB_Query::TYPE_KEY] =  vB_dB_Query::QUERY_UPDATE;
				$nodevals['nodeid'] = $nodeid;
				$success = $this->assertor->assertQuery('vBForum:node', $nodevals);
			}

			//update the parent count if necessary
			if ($updateParents)
			{
				$this->updateParentCounts($nodeid, $textChange, $textUnPubChange, $totalPubChange, $totalUnPubChange, $published);
			}

			//update viewperms from childs if needed, do we want this channel specific?
			if (isset($nodevals['viewperms']) AND isset($prior['viewperms']) AND ($nodevals['viewperms'] != $prior['viewperms']))
			{
				vB_Api::instanceInternal('node')->setNodePerms($nodeid, array('viewperms' => $nodevals['viewperms']));
			}

			if ($index)
			{
				vB_Api::instanceInternal('Search')->index($nodeid);
			}

			// update user tags
			$tags = !empty($data['tags']) ? explode(',', $data['tags']) : array();
			$tagRet = vB_Api::instanceInternal('tags')->updateUserTags($nodeid, $tags);

			$this->updateNodeOptions($nodeid, $data);

			// Update childs nodeoptions
			$this->assertor->assertQuery('vBForum:updateChildsNodeoptions', array(
				vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
				'parentid' => $nodeid
			));

			$this->nodeApi->clearCacheEvents(array($nodeid, $prior['parentid']));

			$loginfo = array(
				'nodeid'		=> $prior['nodeid'],
				'nodetitle'		=> $prior['title'],
				'nodeusername'	=> $prior['authorname'],
				'nodeuserid'	=> $prior['userid']
			);

			$extra = array();
			if ($nodevals !== null && isset($nodevals['title']))
			{
				if($prior['title'] != $nodevals['title'])
				{
					$extra = array('newtitle' => $nodevals['title']);
				}
			}

			vB_Library_Admin::logModeratorAction($loginfo, 'node_edited_by_x', $extra);

			$updateEditLog = true;
			if (
				!vB::getUserContext()->hasPermission('genericoptions', 'showeditedby')
					AND
				(	(
						isset($content[$nodeid]['edit_reason'])
							AND
						$data['reason'] == $content[$nodeid]['edit_reason']
					)
					OR
					(
						!isset($content[$nodeid]['edit_reason'])
							AND
						empty($data['reason'])
					)
				)
			)
			{
				$updateEditLog = false;
			}

			// Clear autosave table of this items entry
			if (vB::getCurrentSession()->get('userid')
					AND
				!empty($data['rawtext'])
			)
			{
				$this->assertor->delete('vBForum:autosavetext', array(
					'userid'   => vB::getCurrentSession()->get('userid'),
					'nodeid'   => $nodeid,
					'parentid' => $content[$nodeid]['parentid']
				));
			}

			// Log edit by info
			if (
				$updateEditLog
					AND
				$this->contenttypeid != $channelContentTypeId
					AND
				isset($content[$nodeid]['rawtext'])
					AND
				isset($data['rawtext'])
					AND
				$content[$nodeid]['rawtext'] != $data['rawtext']
					AND
				$data['publishdate']	// Is this still published?
					AND
				$prior['publishdate']	// Was this already published?
					AND
				(
					!empty($data['reason'])
						OR
					$data['publishdate'] < (vB::getRequest()->getTimeNow() - ($this->options['noeditedbytime'] * 60))
				)
			)
			{
				$userinfo = vB::getCurrentSession()->fetch_userinfo();
				// save the postedithistory
				if ($this->options['postedithistory'])
				{
					$record = $this->assertor->getRow('vBForum:postedithistory',
						array(
							vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
							'original' => 1,
							'nodeid'   => $nodeid
					));
					// insert original post on first edit
					if (empty($record))
					{
						$this->assertor->assertQuery('vBForum:postedithistory', array(
							vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
							'nodeid'   => $nodeid,
							'userid'   => $content[$nodeid]['userid'],
							'username' => $content[$nodeid]['authorname'],
							'dateline' => $data['publishdate'],
							'pagetext' => $content[$nodeid]['rawtext'],
							'original' => 1,
						));
					}
					// insert the new version
					$this->assertor->assertQuery('vBForum:postedithistory', array(
						vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_INSERT,
						'nodeid'   => $nodeid,
						'userid'   => $userinfo['userid'],
						'username' => $userinfo['username'],
						'dateline' => vB::getRequest()->getTimeNow(),
						'reason'   => isset($data['reason']) ? vB5_String::htmlSpecialCharsUni($data['reason']) : '',
						'pagetext' => isset($data['rawtext']) ? $data['rawtext'] : ''
					));
				}

				$this->assertor->assertQuery('editlog_replacerecord', array(
					'nodeid'     => $nodeid,
					'userid'     => $userinfo['userid'],
					'username'   => $userinfo['username'],
					'timenow'    => vB::getRequest()->getTimeNow(),
					'reason'     => isset($data['reason']) ? vB5_String::htmlSpecialCharsUni($data['reason']) : '',
					'hashistory' => intval($this->options['postedithistory'])
				));
			}

			return true;
		}

		$this->nodeApi->clearCacheEvents(array($nodeid, $prior['parentid']));
		return false;
	}


	/*** Returns a content api of the appropriate type
	 *
	 *	@param	int		the content type id
	 *
	 *	@return	mixed	content api object
	 ****/
	public static function getContentApi($contenttypeid)
	{
		return vB_Api::instanceInternal('Content_' . vB_Types::instance()->getContentTypeClass($contenttypeid));
	}

	/*** Returns a content api of the appropriate type
	 *
	 *	@param	int		the content type id
	 *
	 *	@return	mixed	content api object
	 ****/
	public static function getContentLib($contenttypeid)
	{
		return vB_Library::instance('Content_' . vB_Types::instance()->getContentTypeClass($contenttypeid));
	}

	/*** Returns the node content as an associative array
	 *	@param	integer	The id in the primary table
	 *	@param	integer	The id in the primary table
	 *	@param	mixed	array of permissions request- (array group, permission)

	 *	@return	int
	 ***/
	public function getContent($nodeids)
	{
		//With the performance enhancements including search api caching, optimized queries, and content caching
		//it is just as fast to always return the channel id's. That allow code optimization for permission handling
		return $this->getFullContent($nodeids);
	}

	/*** Returns the node content plus the channel routeid and title, and starter route and title the as an associative array
	 *	@param	integer	The id in the primary table
	 *	@param	mixed	array of permissions request- (array group, permission)
	 *
	 *	@return	mixed
	 ***/
	public function getBareContent($nodeids)
	{
		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}

		$nodesContent = $this->getRawContent($nodeids);
		$result = array();

		foreach ($nodeids AS $nodeid)
		{
			if (array_key_exists($nodeid, $nodesContent))
			{
				$result[$nodeid] = $nodesContent[$nodeid];
			}
		}
		return $result;
	}


	/*** Returns the node content plus the channel routeid and title, and starter route and title the as an associative array
	 *	@param	integer	The id in the primary table
	 *	@param	mixed	array of permissions request- (array group, permission)
	 *
	 *	@return	mixed
	 ***/
	public function getFullContent($nodeids)
	{
		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}
		$nodesContent = $this->getRawContent($nodeids);
		//@TODO need to sort out how to improve the content being cached. Not possible now cause we do some perm checks and add flags for the current user.
		$nodesContent = $this->assembleContent($nodesContent);

		$result = array();

		foreach ($nodeids AS $nodeid)
		{
			if (array_key_exists($nodeid, $nodesContent))
			{
				$result[$nodeid] = $nodesContent[$nodeid];
			}
		}
		return $result;
	}

	/** Prepares basic content.  Used by both getBareContent and getFullContent.

	**/
	protected function getRawContent($nodeids)
	{
		//First see what we can load from cache.
		$cached = self::fetchFromCache($nodeids, self::CACHELEVEL_FULLCONTENT);
		$nodesContent = array();
		$timeNow = vB::getRequest()->getTimeNow();
		foreach($cached['found'] AS $key => $cachedNode)
		{
			if (($cachedNode['nextupdate'] > 0) AND ($cachedNode['nextupdate'] > ($cachedNode['lastupdate']))
				AND ($cachedNode['nextupdate'] <= $timeNow))
			{
				unset($cached['found'][$key]);
				$cached['notfound'][] = $cachedNode['nodeid'];
			}
			else
			{
				$nodesContent[$cachedNode['nodeid']] = $cachedNode;
			}
		}

		//Now do we need to query?
		if (!empty($cached['notfound']))
		{
			$content = $this->assertor->getRows('vBForum:getFullContent', array(
				'tablename' => $this->tablename,
				'nodeid' => $cached['notfound'],
			));

			//Now we merge these plus, if necessary, the permissions. First let's make an associative array of the core data.
			if (!$content OR !empty($content['errors']))
			{
				throw new vB_Exception_Api('invalid_data_requested') ;
			}

			// Get prefix phrases
			$phrasevars = array();
			foreach ($content as $node)
			{
				if (!empty($node['prefixid']))
				{
					$phrasevars[] = 'prefix_' .  $node['prefixid'] . '_title_plain';
					$phrasevars[] = 'prefix_' .  $node['prefixid'] . '_title_rich';
				}
				if (!empty($node['starterprefixid']))
				{
					$phrasevars[] = 'prefix_' .  $node['starterprefixid'] . '_title_plain';
					$phrasevars[] = 'prefix_' .  $node['starterprefixid'] . '_title_rich';
				}
			}

			$phrases = array();
			if ($phrasevars)
			{
				$phrases = vB_Api::instanceInternal('phrase')->fetch($phrasevars);
			}

			//Check to see what needs updates.
			$requery = array();
			foreach ($content as $key => $node)
			{
				if (isset($node['nextupdate']) AND ($node['nextupdate'] > 0)  AND ($node['nextupdate'] > $node['lastupdate'])
					AND ($node['nextupdate'] <= $timeNow))
				{
					$this->timedCountUpdates($node['nodeid']);
					$requery[] = $node['nodeid'];
				}

				if ($phrases AND !empty($node['prefixid']))
				{
					$content[$key]['prefix_plain'] = $phrases['prefix_' .  $node['prefixid'] . '_title_plain'];
					$content[$key]['prefix_rich'] = $phrases['prefix_' .  $node['prefixid'] . '_title_rich'];
				}

				if ($phrases AND !empty($node['starterprefixid']))
				{
					$content[$key]['starterprefix_plain'] = $phrases['prefix_' .  $node['starterprefixid'] . '_title_plain'];
					$content[$key]['starterprefix_rich'] = $phrases['prefix_' .  $node['starterprefixid'] . '_title_rich'];
				}
			}

			if (!empty($requery))
			{
				$content2 = $this->assertor->getRows('vBForum:getFullContent',
				array('tablename' => $this->tablename,
				'nodeid' => $requery));

				$content = array_merge($content, $content2);
			}

			self::writeToCache($content, self::CACHELEVEL_FULLCONTENT);

			foreach($content AS $node)
			{
				$nodesContent[$node['nodeid']] = $node;
			}
		}
		return $nodesContent;

	}


	/** Determines whether the current user can edit a node
	*
	*	@param	int		The nodeid
	* 	@param	int		optional usercontext
	 *  @param	mixed	optional array of channel permissions data, which if available prevents a userContext Call
	*
	*	@return	bool
	**/
	public function getCanEdit($node, $userContext = null, $channelPerms = array())
	{
		if (($userContext === null) AND empty($channelPerms))
		{
			$userContext = vB::getUserContext();
		}

		if ($userContext->isSuperAdmin())
		{
			return true;
		}

		if (empty($node['channelid']))
		{
			if ($node['contenttypeid'] == $this->channelTypeId)
			{
				$node['channelid'] = $node['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($node['starter']);
				$node['channelid'] = $starter['parentid'];
			}
		}

		//If it's a channel then users need a higher permission.
		if (empty($node['starter']))
		{
			if (!isset($channelPerms['canconfigchannel']))
			{
				$channelPerms['canconfigchannel'] = $userContext->getChannelPermission('forumpermission2', 'canconfigchannel', $node['nodeid'], false, $node['channelid']);
			}
			return $channelPerms['canconfigchannel'];
		}

		if (!isset($channelPerms['moderate']))
		{
			if ($userContext->getChannelPermission('moderatorpermissions', 'caneditposts', $node['nodeid'], false, $node['channelid']))
			{
				return true;
			}
		}

		if ($channelPerms['moderate']['caneditposts'])
		{
			return true;
		}

		// allow the users to edit VMs sent to themselves if they have permission
		if (!empty($node['setfor']) AND ($node['setfor'] == vB::getCurrentSession()->get('userid')) AND
			($node['setfor'] == $node['userid']) )
		{
			if (!isset($channelPerms['global']))
			{
				$channelPerms['global']['caneditownmessages'] = $userContext->hasPermission('visitormessagepermissions', 'caneditownmessages');
			}
			return $channelPerms['global']['caneditownmessages'];
		}

		//if the user is not the author, we're done.
		if ($node['userid'] != vB::getCurrentSession()->get('userid'))
		{
			return false;
		}

		if (!isset($channelPerms['caneditown']))
		{
			$channelPerms['caneditown'] = $userContext->getChannelPermission('forumpermissions', 'caneditpost', $node['nodeid'], false, $node['channelid']);
		}

		//If the user doesn't have permission to edit their own post we're done.
		if (!$channelPerms['caneditown'])
		{
			return false;
		}

		if (!isset($channelPerms['limits']))
		{
			$channelPerms['limits'] = vB::getUserContext()->getChannelLimits($node['nodeid']);
		}

		if (empty($channelPerms['limits']['edit_time']))
		{
			//There is no edit timeout set;
			return true;
		}

		if ($node['publishdate'] + ($channelPerms['limits']['edit_time'] * 3600) <= vB::getRequest()->getTimeNow())
		{
			return false;
		}
		return true;
	}


	/** Determines whether the current user can delete
	 *
	 *	@param	mixed	The node
	 * 	@param	int		optional usercontext
	 *  @param	mixed	optional array of channel permissions data, which if available prevents a userContext Call
	 *
	 *	@return	bool
	 **/
	public function getCanDelete($node, $userContext, $channelPerms = array())
	{
		if (!empty($channelPerms) AND $channelPerms['global']['is_superadmin'])
		{
			return true;
		}

		if (empty($channelPerms) AND empty($userContext))
		{
			$userContext = vB::getUserContext();
		}

		if ( $userContext->isSuperAdmin())
		{
			return true;
		}

		if (empty($node['channelid']))
		{
			if ($node['contenttypeid'] == $this->channelTypeId)
			{
				$node['channelid'] = $node['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($node['starter']);
				$node['channelid'] = $starter['parentid'];
			}
		}

		if (!isset($channelPerms['canremoveposts']))
		{
			$channelPerms['canremoveposts'] =
				$userContext->getChannelPermission('moderatorpermissions', 'canremoveposts', $node['nodeid'], false, $node['channelid']);
		}

		if ($channelPerms['canremoveposts'])
		{
			return true;
		}
		else
		{
			// The user may be the owner of the channel
			if ($userContext->getChannelPermission('forumpermissions2', 'canmanageownchannels', $node['nodeid'], false, $node['channelid']))
			{
				$channel = vB_Api::instanceInternal('node')->getNode($node['channelid']);

				if (($channel['userid'] == vB::getCurrentSession()->get('userid')) AND ($node['nodeid'] != $channel['nodeid']))
				{
					return true;
				}
			}
		}

		//If this is the current user's post or thread we might be able to delete.
		if (($node['userid'] != vB::getCurrentSession()->get('userid')))
		{
			return false;
		}

		if ($node['starter'] == $node['nodeid'])
		{
			if (!isset($channelPerms['candeleteownthread']))
			{
				$channelPerms['candeleteownthread'] =
					$userContext->getChannelPermission('forumpermissions', 'candeletethread', $node['nodeid'], false, $node['channelid']);
			}
			return !empty($channelPerms['candeleteownthread']);
		}
		else if ($node['starter'] > 0)
		{
			if (!isset($channelPerms['candeleteownpost']))
			{
				$channelPerms['candeleteownpost'] =
					$userContext->getChannelPermission('forumpermissions', 'candeletepost', $node['nodeid'], false, $node['channelid']);
			}
			return !empty($channelPerms['candeleteownpost']);
		}
		//This means it's a channel.
		return false;
	}


	/** Determines whether the current user can moderate
	 *
	 *	@param	mixed	The node
	 * 	@param	int		optional usercontext
	 *  @param	mixed	optional array of channel permissions data, which if available prevents a userContext Call
	 *	@param	int		optional nodeid,
	 *
	 *	@return	bool
	 **/
	public function getCanModerate($node, $userContext, $channelPerms = array(), $nodeid = 0)
	{

		if (!empty($channelPerms) AND $channelPerms['global']['is_superadmin'])
		{
			return true;
		}

		if (empty($channelPerms) AND empty($userContext))
		{
			$userContext = vB::getUserContext();
		}

		if ( $userContext->isSuperAdmin())
		{
			return true;
		}

		if (!is_array($node) and intval($nodeid))
		{
			$node = vB_Library::instance('node')->getNodeBare($nodeid);
		}

		if (empty($node['channelid']))
		{
			if ($node['contenttypeid'] == $this->channelTypeId)
			{
				$node['channelid'] = $node['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($node['starter']);
				$node['channelid'] = $starter['parentid'];
			}
		}

		if (!isset($channelPerms['canmanageownchannels']))
		{
			$channelPerms['canmanageownchannels'] =
				$userContext->getChannelPermission('moderatorpermissions', 'canmoderateposts', $node['nodeid'], false, $node['channelid']);
		}

		if ($channelPerms['canmanageownchannels'])
		{
			return true;
		}

		$channel = vB_Library::instance('node')->getNodeBare($node['channelid']);

		if ($channel['userid'] == vB::getCurrentSession()->get('userid'))
		{
			$channel = vB_Library::instance('node')->getNodeBare($node['channelid']);

			if ($channel['userid'] == vB::getCurrentSession()->get('userid'))
			{
				$channelPerms['canmanageownchannels'] =
					$userContext->getChannelPermission('forumpermissions2', 'canmanageownchannels', $node['nodeid'], false, $node['channelid']);
			}

			return !empty($channelPerms['canmanageownchannels']);
		}

		return false;
	}

	/*** Assembles the response for detailed content
	 *
	 *	@param	mixed	assertor response object
	 *
	 *	@return	mixed	formatted data
	 ***/
	public function assembleContent(&$content)
	{
		// get the class name for this content type to add to results
		$contentTypeClass = vB_Types::instance()->getContentTypeClass($this->contenttypeid);
		$userContext = vB::getUserContext();
		$languageid = vB::getCurrentSession()->get('languageid');
		//We can save some time by saving, for a page load, the list of
		// channels we already know the current user can't post.

		static $noComment = array();
		$results = array();
		$needUserRep = array();
		$cache = vB_Cache::instance(vB_Cache::CACHE_STD);
		$fastCache = vB_Cache::instance(vB_Cache::CACHE_FAST);
		$thisUserid = vB::getCurrentSession()->get('userid');
		//pre-cache channels
		$channelids = $nodeids = array();
		foreach($content AS $key => $record)
		{
			if (!$this->checkComplete($record))
			{
				unset($content[$key]);
			}

			if (!empty($record['channelid']))
			{
				$channelids[$record['channelid']] = $record['channelid'];
			}
			else if ($record['contenttypeid'] == $this->channelTypeId)
			{
				$content[$key]['channelid'] = $record['nodeid'];
				$channelids[$record['nodeid']] = $record['nodeid'];
			}
			else
			{
				$starter = vB_Library::instance('node')->getNodeBare($record['starter']);
				$content[$key]['channelid'] = $starter['parentid'];
				$channelids[$starter['parentid']] = $starter['parentid'];
			}
			$nodeids[] = $record['nodeid'];
		}

		// preload closure
		vB_Library::instance('node')->fetchClosureParent($nodeids);
		$channels = vB_Library::instance('node')->getNodes($channelids);
		//and preload channel permission data
		$channelPerms = vB::getUserContext()->fetchPermsForChannels($channelids);
		$needOnlineStatus = array();
		$needReputation = array();
		$contentUserids = array();
		$channelTypes = vB::getDatastore()->getValue('vBChannelTypes');

		//we can comment if there is at least one content type we can create, and the channel (in case this is a blog or
		//social group) doesn't have comments disabled, and either it's your and you have canreplyown or it's somebody else's and you
		// have canreplyothers. So that's going to require another scan of the records
		$userid = vB::getCurrentSession()->get('userid');
		$commentsEnabled =  vB::getDatastore()->getOption('postcommentthreads');
		$canmanageownprofile = $channelPerms['global']['canmanageownprofile'];
		foreach($content AS $key => $record)
		{
			$record['contenttypeclass'] = $contentTypeClass;
			$record['canview'] = 1;
			$record['can_comment'] = 0;
			$channelid = $record['channelid'];
			$thisChannelPerms = $channelPerms[$channelid];
			$record['createpermissions'] = $thisChannelPerms['cancreate'];
			$record['moderatorperms'] = $thisChannelPerms['moderate'];

			//channeltype
			if (isset($channelTypes[$channelid]))
			{
				$record['channeltype'] = $channelTypes[$channelid];
			}
			else
			{
				$record['channeltype'] = '';
			}

			//if the current user can't view thread content here we have to unset a number of fields.
			if (!$thisChannelPerms['canviewthreads'])
			{
				foreach($record AS $fieldname => $value)
				{
					if (!isset($this->allCanview[$fieldname]))
					{
						unset($content[$key][$fieldname]);
					}
				}
			}

			$thisChannelPerms['global'] = $channelPerms['global'];
			$record['canedit'] = (int)$this->getCanEdit($record, $userContext, $thisChannelPerms);

			//There are four moderator-like permissions. Let's start setting them to zero. If the user has that
			// permissions we'll enable it soon.
			foreach (array('canmove', 'candeleteposts', 'candeletethread', 'canopenclose') AS $permName)
			{
				$record[$permName] = 0;
			}

			if ($record['contenttypeid'] == $this->channelTypeId)
			{
				$record['canundeleteposts'] = $record['canharddeleteposts'] = $record['canremove'] = false;
			}
			else
			{
				$record['canundeleteposts'] = $record['canharddeleteposts'] = $record['canremove'] = $thisChannelPerms['canremoveposts'];
			}

			if ($this->getCanDelete($record, $userContext, $thisChannelPerms))
			{
				$record['candeleteposts'] = 1;
				if ($record['starter'] == $record['nodeid'])
				{
					$record['candeletethread'] = 1;
				}
			}

			$record['moderate']['candeleteposts'] = $thisChannelPerms['moderate']['canundeleteposts'] = 0;
			$thisChannelPerms['canmoderateposts'] = $this->getCanModerate($record, $userContext, $thisChannelPerms);

			if ($thisChannelPerms['canmoderateposts'])
			{
				$record['canmoderate'] = $record['canmoderateposts'] = 1;
				$record['moderate']['canmoderateposts'] = $thisChannelPerms['moderate']['canmoderateposts'] = 1;
			}
			else
			{
				$record['canmoderate'] = 0;
			}

			//check the four 'my own' moderator-like permissions
			if ($record['showopen'] AND $record['userid'] == $thisUserid)
			{
				//and the four moderator-like permissions for node owners.
				//move is for the topic, not replies & comments
				if (($record['nodeid'] == $record['starter']) AND $thisChannelPerms['canmove'])
				{
					$record['moderatorperms']['canmove'] = 1;
					$record['moderatorperms']['canmovethread'] = 1;
				}

				if (($record['nodeid'] == $record['starter']) AND $thisChannelPerms['canopenclose'])
				{
					$record['moderatorperms']['canopenclose'] = 1;
				}

				if (($record['nodeid'] == $record['starter']) AND $thisChannelPerms['candeleteownthread'])
				{
					$record['moderatorperms']['user_candeleteownthread'] = 1;
				}

				if (($record['nodeid'] != $record['starter']) AND $thisChannelPerms['candeleteownpost'])
				{
					$record['moderatorperms']['candeleteposts'] = 1;
				}

				if ($thisChannelPerms['caneditown'])
				{
					$record['moderatorperms']['caneditpost'] = 1;
				}
			}

			// allow the receiver to manage their VMs if they have permission
			if (!empty($record['setfor']) AND ($record['setfor'] == $userid) AND $canmanageownprofile)
			{
				$record['canmoderateposts'] = 1;
				$record['candeleteposts'] = 1;
				$thisChannelPerms['moderate']['canmoderateposts'] = 1;
			}

			if (($this->textCountChange > 0) AND $this->isPublished($record))
			{
				$record['textcount_1'] = $record['textcount'] + 1 ;
				$record['totalcount_1'] = $record['totalcount'] + 1 ;

				if($record['canmoderate'])
				{
					$record['textcount_1'] += $record['textunpubcount'];
					$record['totalcount_1'] += $record['totalunpubcount'];
				}
			}
			else
			{
				$record['textcount_1'] = $record['textcount'];
				$record['totalcount_1'] = $record['totalcount'] ;
			}

			// Add userinfo for reputation - is there a cached version?
			if (($record['userid'] > 0) AND ($record['contenttypeid'] != $this->channelTypeId))
			{
				$needReputation['vBUserRep_' . $record['userid']] = 'vBUserRep_' . $record['userid'];
			}
			$record['allow_post'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST) ? 1 : 0;
			$record['moderate_comments'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_MODERATE_COMMENTS) ? 1 : 0;
			$record['approve_membership'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_AUTOAPPROVE_MEMBERSHIP) ? 1 : 0;
			$record['invite_only'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_INVITEONLY) ? 1 : 0;
			$record['autoparselinks'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_PARSELINKS) ? 1 : 0;
			$record['disablesmilies'] = ($record['nodeoptions'] & vB_Api_Node::OPTION_NODE_DISABLE_SMILIES) ? 1 : 0;
			$record['createpermissions'] = $thisChannelPerms['cancreate'];

			$record['can_flag'] = intval($userid) ? 1 : 0;

			//If this is not a channel, we need the user information to check reputation.
			if ($record['contenttypeid'] != $this->channelTypeId)
			{
				$contentUserids[] = $record['userid'];
				// these cache keys are set by vB_Library_User::preloadUserInfo
				$needOnlineStatus[$record['userid']] = "vb_UserInfo_{$record['userid']}_$languageid";
				//Now the moderator-type permissions

				if (!empty($record['moderatorperms']))
				{
					foreach ($record['moderatorperms'] AS $key => $perm)
					{
						if (($perm > 0) AND ($key != 'caneditpost'))
						{
							$record['canmoderate'] = 1;
							break;
						}
					}
				}
			}

			$infractionLibrary = vB_Library::instance('content_infraction');
			$record['caninfract'] = $infractionLibrary->canInfractNode($record['nodeid'], $record) ? 1 : 0;
			$record['canviewnodeinfraction'] = $infractionLibrary->canViewNodeInfraction($record['nodeid'], $record) ? 1 : 0;

			$record['canseewholiked'] = $userContext->hasPermission('genericpermissions', 'canseewholiked') ? 1 : 0;

			if (!empty($record['starteruserid']))
			{
				$authorid = $record['starteruserid'];
			}
			else if ($record['starter'] == $record['nodeid'])
			{
				$authorid = $record['userid'];
			}
			else if ($record['starter'] > 0)
			{
				$starter = vB_Library::instance('node')->getNodeBare($record['starter']);
				$authorid = $starter['userid'];
			}
			else
			{
				$authorid = -1;
			}

			if (($record['starter'] > 0) AND ($userid == $authorid) AND
				!$thisChannelPerms['canreplyown'])
			{
				$record['createpermissions'] = false;
			}
			else if (($record['starter'] > 0) AND ($userid != $authorid) AND
				!$thisChannelPerms['canreplyothers'])
			{
				$record['createpermissions'] = false;
			}
			else if ((($record['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST) > 0)
				AND ($commentsEnabled OR ($record['starter'] == 0) OR ($record['starter'] == $record['nodeid']) ) )
			{
				foreach ($thisChannelPerms['cancreate'] AS $name => $perm)
				{
					// remove special content types from can_comment granting since they have a different purpose
					// @TODO remove calendar from here when implemented and user can comment on it
					if (intval($perm) AND !in_array($name, array('vbforum_channel', 'vbforum_report', 'vbforum_privatemessage', 'vbforum_calendar')))
					{
						$record['can_comment'] = 1;
						break;
					}
				}

				if (($record['can_comment'] == 1) AND !empty($record['starter']))
				{
					//If we were called with fullcontent then we already have the channel.
					if (empty($record['channelid']))
					{
						$thisParent = $this->nodeApi->getNode($record['starter']);
						$record['channelid'] = $thisParent['parentid'];
					}

					if (empty($channels[$record['channelid']]))
					{
						$channels[$record['channelid']] = $this->nodeApi->getNode($record['channelid']);
					}
						//We need to check the nodeoptions field.
					if ($channels[$record['channelid']]['nodeoptions'] & vB_Api_Node::OPTION_ALLOW_POST == 0)
					{
						$record['can_comment'] = 0;
					}
				}
			}
			$record['permissions'] = array('canedit' => $record['canedit'], 'canmoderate' => $record['canmoderate'],
				'canvote' => ($thisChannelPerms['canvote'] ? 1 : 0), 'canremove' => $record['canremove'],
				'can_flag' => $record['can_flag'], 'canviewthreads' =>  $thisChannelPerms['canviewthreads'],
				'caninfract' => $record['caninfract'], 'canviewnodeinfraction' => $record['canviewnodeinfraction'],
				'canseewholiked' => $record['canseewholiked']);

			// can't vote on an infraction
			if ($record['permissions']['canvote'] AND $this->contenttype == 'vBForum_Infraction')
			{
				$record['permissions']['canvote'] = 0;
			}
			$record['moderatorperms']['canharddeleteposts'] = (int)$record['canharddeleteposts'];
			$record['moderatorperms']['candeleteposts'] = (int)$record['candeleteposts'];
			$record['moderatorperms']['canundeleteposts'] = (int)$record['canundeleteposts'];
			$record['moderatorperms']['candeletethread'] = empty($record['candeletethread']) ? 0 : 1;
			$record['moderatorperms']['canmoderateposts'] = empty($record['canmoderateposts']) ? 0 : 1;
			$results[$record['nodeid']] = $record;
		}

		if (!empty($contentUserids))
		{
			vB_Library::instance('user')->preloadUserInfo($contentUserids);
			// Add online status
			require_once(DIR . '/includes/functions_bigthree.php');
			// we just preloaded this info, so there must be a cache hit
			$cached = $fastCache->read($needOnlineStatus);
			$loadedSigPerm = array();
			foreach($results AS $key => $record)
			{
				if ($record['userid'] == 0)
				{
					continue;
				}
				$cache_key = "vb_UserInfo_{$record['userid']}_$languageid";
				$authorInfo = $cached[$cache_key];

				$results[$key]['signature'] = $authorInfo['signature'];
				if (!empty($authorInfo['signature']))
				{
					if (empty($loadedSigPerm[$record['userid']]))
					{
						$loadedSigPerm[$record['userid']] = vB::getUserContext($record['userid'])->hasPermission('genericpermissions', 'canusesignature');
					}
					$results[$key]['canSign'] = $loadedSigPerm[$record['userid']] ? 1 : 0;
				}

				$results[$key]['musername'] = vB_Api::instanceInternal("user")->fetchMusername($authorInfo);

				if (!isset($authorInfo['online']))
				{
					fetch_online_status($authorInfo);
				}
				$results[$key]['online'] = $authorInfo['online'];
				$options = vB::getDatastore()->getValue('options');

				if(
					!empty($options['postelements'])
					//postelements = 4 = showinfractions setting
					AND $options['postelements'] == 4
					//Check that we have at least one value
					AND ( $authorInfo['ipoints']
						OR $authorInfo['warnings']
						OR $authorInfo['infractions']
					)
					//Check permissions and the user is the same logged in
					AND ( $userContext->hasPermission('genericpermissions', 'canreverseinfraction')
						OR $userContext->hasPermission('genericpermissions', 'canseeinfraction')
						OR $userContext->hasPermission('genericpermissions', 'cangiveinfraction')
						OR vB::getCurrentSession()->get('userid') == $authorInfo['userid']
					)
				)
				{
					$results[$key]['postelements'] = $options['postelements'];
					$results[$key]['ipoints'] = $authorInfo['ipoints'];
					$results[$key]['warnings'] = $authorInfo['warnings'];
					$results[$key]['infractions'] = $authorInfo['infractions'];
				}

			}
		}
		if (!empty($needReputation))
		{
			$cached = $cache->read($needReputation);
			foreach($content AS $record)
			{
				// Add userinfo for reputation - is there a cached version?
				if (($record['userid'] > 0) AND ($record['contenttypeid'] != $this->channelTypeId))
				{

					if ($cached['vBUserRep_' . $record['userid']] !== false)
					{
						$cacheitem = $cached['vBUserRep_' . $record['userid']];
						$results[$record['nodeid']]['reputation'] = $cacheitem['reputation'];
						$results[$record['nodeid']]['showreputation'] = $cacheitem['showreputation'];
						$results[$record['nodeid']]['reputationlevelid'] = $cacheitem['reputationlevelid'];
						$results[$record['nodeid']]['reputationpower'] = $cacheitem['reputationpower'];
						$results[$record['nodeid']]['reputationimg'] = $cacheitem['reputationimg'];
					}
					else
					{
						$needUserRep[$record['nodeid']] = $record['userid'];
					}
				}
			}
		}

		//Now add reputation for any users for which we didn't have a cached value.
		if (!empty($needUserRep))
		{
			$reputationLib = vB_Library::instance('reputation');
			$userInfo = $this->assertor->assertQuery('user', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
				'userid' => $needUserRep));
			$bf_misc_useroptions = vB::getDatastore()->getValue('bf_misc_useroptions');

			$userReps = array();
			//build the reputation information
			foreach($userInfo AS $authorInfo)
			{
				$userid = $authorInfo['userid'];
				$userReps[$userid] = array();
				$userReps[$userid]['reputation'] = $authorInfo['reputation'];
				$userReps[$userid]['showreputation'] = $authorInfo['options'] & $bf_misc_useroptions['showreputation'];
				$userReps[$userid]['reputationlevelid'] = $authorInfo['reputationlevelid'];
				$userReps[$userid]['reputationpower'] = $reputationLib->fetchReppower($authorInfo);
				$userReps[$userid]['reputationimg'] = $reputationLib->fetchReputationImageInfo($authorInfo);
				//cache this for a day
				$cache->write('vBUserRep_' . $userid, $userReps[$userid], 1440, array("fUserContentChg_$userid", "userChg_$userid"));

			}
			foreach($needUserRep AS $nodeid => $userid)
			{
				if (!empty($userReps[$userid]))
				{
					foreach ($userReps[$userid] AS $field => $val)
					{
						$results[$nodeid][$field] = $val;
					}
				}
			}
		}

		return $results;
	}

	/** Checks for any subcontent that has either a publish or unpublish date
	 *
	 *	@param	int		the nodeid we are updating.
	 *
	 ***/
	public function timedCountUpdates($nodeid)
	{
		static $processed = array();

		if (in_array($nodeid, $processed))
		{
			return true;
		}
		$processed[] = $nodeid;
		$timeNow = vB::getRequest()->getTimeNow();

		//First get a list of expired items.
		$query = $this->assertor->assertQuery('vBForum:getNeedUpdate', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
		 'nodeid' => $nodeid, 'timenow' => $timeNow));

		$updates = array();
		foreach ($query as $node)
		{
			$updates[] = $node['nodeid'];
		}

		//Now below we're going to reset the nextupdate date. So let's clear it for the nodes we're going to process now.
		$this->assertor->assertQuery('vBForum:node', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_UPDATE,
			'nodeid' => $nodeid, 'nextupdate' => 0));

		if (empty($updates))
		{
			return true;
		}

		$needRebuild = false;
		$nodes = $this->nodeApi->getNodes($updates);
		foreach ($nodes as $node)
		{
			if (($node['publishdate'] > 0) AND
				($node['publishdate'] <= $timeNow) AND
				(($node['unpublishdate'] <= 0) OR ($node['unpublishdate'] >= $timeNow) OR
				($node['unpublishdate'] < $node['publishdate'])
				))
			{
				$published = 1;
			}
			else
			{
				$published = 0;
			}

			//see if we need a rebuild
			if ($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Channel'))
			{
				$needRebuild = true;
			}
			//We are concerned about two possibilities. It could have gone from published to unpublished.
			//From published to unpublished.
			if ($published)
			{
				$textChange = $this->textCountChange;
				$textUnPubChange = -1 * $textChange;
				$totalPubChange = $node['totalunpubcount'] + $textChange;
				$totalUnPubChange = -1 * $totalPubChange;
			}
			//or from unpublished to published.
			else
			{
				$textUnPubChange = $this->textCountChange;
				$textChange = -1 * $textUnPubChange;
				$totalUnPubChange = $node['totalcount'] + $textUnPubChange;
				$totalPubChange = -1 * $totalUnPubChange;
			}

			// now update the timestamps
			$nextUpdate = 0;

			if ($node['publishdate'] > $timeNow)
			{
				$nextUpdate = $node['publishdate'];
			}

			if ($node['unpublishdate'] > $timeNow AND
				(($nextUpdate == 0) OR ($nextUpdate > $node['unpublishdate'])))
			{
				$nextUpdate = $node['unpublishdate'];
			}

			$this->assertor->update('vBForum:node', array(
				'lastupdate' => $timeNow,
				'nextupdate' => $nextUpdate,
				'showpublished' => $published
			), array('nodeid' => $node['nodeid']));

			$this->updateParentCounts($node['nodeid'], $textChange, $textUnPubChange, $totalPubChange, $totalUnPubChange, $published);

			$this->assertor->assertQuery('vBForum:setNextUpdate', array('lastupdate' => $timeNow, 'nodeid' => $node['nodeid'],
				'publishdate' => intval($node['publishdate']), 'unpublishdate' => intval($node['unpublishdate'])));

		}
		//The only reason this happens is that something has become either published or unpublished.
		vb_Cache::instance()->event('perms_changed');

		if ($needRebuild)
		{
			vB::getUserContext()->rebuildGroupAccess();
		}
	}

	public function getIndexableFromNode($node, $include_attachments = true)
	{
		//merge in the attachments if any
		if ($include_attachments)
		{
			$indexableContent = $this->getIndexableContentForAttachments($node['nodeid']);
		}
		else
		{
			$indexableContnet = array();
		}
		$indexableContent['title'] = isset($node['title']) ? $node['title'] : '';
		return $indexableContent;

	}

	/**
	 * The classes  that inherit this should implement this function
	 * It should return the content that should be indexed
	 * If there is a title field, the array key for that field should be 'title',
	 * the rest of the text can have any key
	 * @param int $nodeId  - it might be the node (assiciative array)
	 * @return array $indexableContent
	 */
	public function getIndexableContent($nodeId, $include_attachments = true)
	{
		// we might already have the content
		if (is_array($nodeId) AND array_key_exists('nodeid', $nodeId))
		{
			$node = $nodeId;
			$nodeId = $node['nodeid'];
		}
		else
		{
			$node = vB_Library::instance('node')->getNodeBare($nodeId);
		}
		if (empty($node))
		{
			return false;
		}
		if(!array_intersect($this->index_fields, array_keys($node)))
		{
			$this->fillContentTableData($node);
		}

		return $this->getIndexableFromNode($node, $include_attachments);
	}

	/*** Finds the correct conversation starter for a node
	 *
	 *	@param	int		nodeid of the item being checked
	 *
	 *	@return	int		the conversation starter's nodeid
	 ***/
	public function getStarter($nodeid)
	{
		$parents = vB_Library::instance('node')->getParents($nodeid);
		$channelContentTypeId = vB_Types::instance()->getContentTypeId('vBForum_Channel');

		foreach ($parents as $sequence => $parent)
		{
			if ($parent['contenttypeid'] == $channelContentTypeId)
			{
				if ($sequence == 0)
				{
					return $nodeid;
				}
				else
				{
					return $parents[$sequence - 1]['nodeid'];
				}
			}
		}
	}

	/*** Gets the main conversation node.
	 *
	 * 	@param	int		the nodeid
	 * 	@return	mixed	the main conversation node
	 */
	public function getConversationParent($nodeid)
	{
		if (empty($nodeid))
		{
			return false;
		}

		$parentId = $this->getStarter($nodeid);
		$parentNode = vB_Library::instance('node')->getNodeBare($parentId);

		return $parentNode;
	}


	/** determines whether a specific node is a visitor message
	 *
	 *	@param	int
	 *
	 *	@return bool
	 **/
	public function isVisitorMessage($nodeid)
	{
		//Something is a visitor message if it's a descendant in the closure table of the protected channel
		// for visitor messages
		$test = vB_Library::instance('node') -> fetchClosureParent($nodeid, $this->nodeApi->fetchVMChannel());
		reset($test);
		$test = current($test);
		return ($test AND !empty($test['child']));
	}

	/**
	 * Determines whether a specific node is a comment on a thread reply or not
	 *
	 * @param	int	Node ID
	 * @param	array	Node information
	 * @return	bool
	 **/
	public function isComment($nodeid, array $node = null)
	{
		if ($node === null)
		{
			$node = vB_Library::instance('node')->getNodeBare($nodeid);
		}

		return (
			// not a channel
			($this->contenttype != 'vBForum_Channel')
			AND
			// not a topic starter
			($node['starter'] != $node['nodeid'])
			AND
			// not a topic reply
			($node['parentid'] != $node['starter'])
		);
	}

	/** Sends notifications when specific events occur
	 *
	 *	@param	mixed	array of about, aboutid, sentto.
	 * 	Notes:	Whenever adding a new notification, be sure to go through this
				instead of privatemessage->add()
	 **/
	public function sendNotifications($notifications)
	{
		$userEmailInfo = array();
		$messageLibrary = vB_Library::instance('Content_Privatemessage');
		$userInfo = vB::getCurrentSession()->fetch_userinfo();
		foreach ($notifications as $notification)
		{
			$data = array(
				'msgtype' => 'notification',
				'about' => $notification['about'],
				'rawtext' => '',
				'aboutid' => $notification['aboutid'],
				'sentto' => $notification['userid'],
				'sender' => $userInfo['userid'],
			);
			// send notification only if receiver is not the sender.
			// this is to prevent user's own actions creating notifications
			// also check receiver's's notification options with userReceivesNotification(userid, about)
			if (($data['sentto'] != $data['sender']) AND $messageLibrary->userReceivesNotification($data['sentto'], $data['about']))
			{
				$nodeid = $messageLibrary->addMessageNoFlood($data);

				if (!isset($userEmailInfo[$notification['userid']]))
				{
					$userInfoTemp = vB_Library::instance('user')->fetchUserinfo($notification['userid']);
					$userEmailInfo[$notification['userid']] = array(
						'email' => $userInfoTemp['email'],
						'autosubscribe' => $userInfoTemp['autosubscribe'],
						'username' => $userInfoTemp['username'],
						'languageid' => $userInfoTemp['languageid'],
					);
					unset($userInfoTemp);
				}
				if ($userEmailInfo[$notification['userid']]['autosubscribe'] == 1)
				{
					$notification['email'] = $userEmailInfo[$notification['userid']]['email'];
					$notification['username'] = $userEmailInfo[$notification['userid']]['username'];
					$notification['languageid'] = $userEmailInfo[$notification['userid']]['languageid'];
					$this->sendEmailNotification($notification);
				}
			}
		}
	}

	/**
	 * Get the indexable content for attachments.  Separate this so that child classes
	 * can rewrite getIndexableContent entirely without having to copy as much code
	 */
	protected function getIndexableContentForAttachments($nodeId)
	{
		$attachments = vB_Api::instanceInternal('Node')->getNodeAttachments($nodeId);
		$indexableContent = array();
		foreach ($attachments as $attach)
		{
			if ($attach['nodeid'] == $nodeId)
			{
				continue;
			}
			$indexableContent[$attach['nodeid']] = $attach['caption'] . ' ' . $attach['filename'];
		}
		return $indexableContent;
	}

	/** returns the tables used by this content type.
	 *
	 *	@return	Array
	 *
	 **/
	public function fetchTableName()
	{
		if (is_array($this->tablename))
		{
			return $this->tablename;
		}
		return array($this->tablename);
	}

	/** This attempts to get the cached data for nodes
	*
	*	@param	mixed		integer or array of integers
	* 	@param	integer		one of the constants for level of data
	*
	*	@return	mixed		array('found' => array of node values per the constant, 'notfound' => array of nodeids);
	*
	***/
	public static function fetchFromCache($nodeids, $level)
	{
		if (!is_array($nodeids))
		{
			$nodeids = array($nodeids);
		}

		$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
		$found = array();
		$notfound = array();
		foreach ($nodeids AS $nodeid)
		{
			if (!intval($nodeid))
			{
				continue;
			}
			switch ($level)
			{
				case self::CACHELEVEL_NODE:
					$hashKey = 'node_' . $nodeid . "_lvl1data";
					$data = $cache->read($hashKey);

					if (!empty($data))
					{
						//Got a hit.
						$found[$nodeid] = $data;
						break;
					}

				case self::CACHELEVEL_CONTENT:
				case self::CACHELEVEL_FULLCONTENT:
					$hashKey = 'node_' . $nodeid . "_lvl3data";
					$data = $cache->read($hashKey);

					if (!empty($data))
					{
						//Got a hit.
						$found[$nodeid] = $data;
						break;
					}
				//Nope- no data.
				$notfound[$nodeid] = $nodeid;
			}
		}
		if (self::$cacheNodes AND !empty($notfound))
		{
			$keys = array();
			foreach ($notfound AS $nodeid)
			{
				//If we're going to query large cache, just get fullcontent
				$keys[] = 'node_' . $nodeid . "_lvl3data";
			}
			$cached = vB_Cache::instance(vB_Cache::CACHE_LARGE)->read($keys);
			if (is_array($cached))
			{
				foreach ($cached AS $cacheid => $cacheRecord)
				{
					if (!empty($cacheRecord))
					{
						//put the value into fastcache
						$cache->write($cacheid, $cacheRecord, 60, 'nodeChg_' . $cacheRecord['nodeid']);
						$found[$cacheRecord['nodeid']] = $cacheRecord;
						unset($notfound[$cacheRecord['nodeid']]);
					}
				}
			}
		}
		return array('found' => $found, 'notfound' => $notfound);
	}

	/** writes new cached data for nodes
	 *
	 *	@param	mixed		array of node values
	 * 	@param	integer		one of the constants for level of data
	 *
	 *	@return	mixed		array('found' => array of node values per the constant, 'notfound' => array of nodeids);
	 *
	 ***/
	public static function writeToCache($nodes, $level)
	{
		$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);

		if (self::$cacheNodes)
		{
			$largeCache = vB_Cache::instance(vB_Cache::CACHE_LARGE);
		}

		foreach ($nodes AS $node)
		{
			//make sure data was passed correctly
			if (!empty($node['nodeid']))
			{
				$hashKey = 'node_' . $node['nodeid'] . "_lvl" . $level .  "data";
				$cache->write($hashKey, $node, 1440, 'nodeChg_' . $node['nodeid']);

				if (self::$cacheNodes)
				{
					$largeCache->write($hashKey, $node, 1440, 'nodeChg_' . $node['nodeid']);;
				}
			}
		}
	}

	/**
	 * Publish this node to facebook feed
	 */
	protected function publishToFacebook($nodeId, array $options = array())
	{
		// did the user request publishing this to facebook?
		if (!isset($options['facebook']) || !isset($options['facebook']['fbpublish']) || !$options['facebook']['fbpublish'])
		{
			return;
		}

		// is facebook connect & publishing available?
		if (!vB_Facebook::isFacebookEnabled())
		{
			return;
		}

		// @todo - do we want to restrict to certain content types?
//		if (!in_array($this->contenttype, array('vBForum_Text', 'vBForum_Link', 'vBForum_Poll', 'vBForum_Video'), true))
//		{
//			return;
//		}

		// get node information
		//$node = $this->nodeApi->getNode($nodeId);
		$node = $this->nodeApi->getContentForNodes(array($nodeId));
		$node = array_shift($node);

		if (!$node || !empty($node['errors']))
		{
			return;
		}

		// is the node published/visible/public in vB?
		if ($node['showpublished'] != 1 || $node['approved'] != 1)
		{
			return;
		}

		$isStarter = ($node['nodeid'] == $node['starter']);

		// can new discussion, photo, link, poll etc be published?
		if ($isStarter && !$this->options['fbfeednewthread'])
		{
			return;
		}

		// can replies to discussion, photo, link, poll etc be published?
		if (!$isStarter && !$this->options['fbfeedpostreply'])
		{
			return;
		}

		// get node URL
		$nodeUrl = $options['facebook']['baseurl'] . vB_Api::instanceInternal('route')->getUrl($node['routeid'] . '|nosession', $node, array());

		if (!vB_Facebook::instance()->userIsLoggedIn())
		{
			return;
		}

		// $message should *really* be set by the plaintext parser, which hasn't been
		// brought over from vB4
		$message = vB_String::stripBbcode($node['content']['rawtext']);
		$previewtext = vB_String::getPreviewText($message);

		vB_Facebook::instance()->publishFeed($message, $node['title'], $nodeUrl, $previewtext);
	}

	protected function verifyPrefixid($prefixid)
	{
		if ($prefixid)
		{
			$prefix = $this->assertor->getRow('vBForum:prefix', array('prefixid' => $prefixid));

			if (!$prefix)
			{
				throw new vB_Exception_Api('invalid_prefixid');
			}

			require_once(DIR . '/includes/functions_prefix.php');

			if (!can_use_prefix($prefixid))
			{
				throw new vB_Exception_Api('invalid_prefixid');
			}
		}
	}

	/**
	 * Verifies that the post iconid is valid
	 *
	 * @param	int	post icon ID
	 */
	protected function verifyPostIconid($posticonid)
	{
		if ($posticonid)
		{
			$posticon = $this->assertor->getRow('icon', array('iconid' => $posticonid));

			if (!$posticon)
			{
				throw new vB_Exception_Api('invalid_posticonid');
			}
		}
	}


	protected function changeContentType($nodeid, $oldcontentType, $newcontentType)
	{
		$newLibrary = $this->getContentLib($newcontentType);
		$oldLibrary = $this->getContentLib($oldcontentType);

		$oldTables = $oldLibrary->fetchTableName();
		$newTables = $newLibrary->fetchTableName();

		$deleteTables = array_diff($oldTables, $newTables);
		$addTables = array_diff($newTables, $oldTables);

		foreach ($deleteTables as $table)
		{
			$this->assertor->delete('vBForum:' . $table, array(
				'nodeid' => $nodeid,
			));
		}

		foreach ($addTables as $table)
		{
			$this->assertor->insert('vBForum:' . $table, array(
				'nodeid' => $nodeid,
			));
		}
		vB_Api::instanceInternal('Search')->attributeChanged($nodeid);
	}

	// We need this empty method to prevent error happening. See VBV-5747.
	protected function sendEmailNotification($data)
	{
	}

	/**
	 * Calculates the CRC based on the indexable content
	 * @param array $data
	 * @return string
	 */
	protected function getCRC32($data)
	{
		try
		{
			$indexableContent = $this->getIndexableFromNode($data, false);
		}
		catch (Exception $e)
		{
			$indexableContent = array();
		}
		return sprintf('%u', crc32(implode(' ', $indexableContent)));
	}

	protected function fillContentTableData(&$node)
	{
		$contentData = $this->assertor->getRow('vBForum:getContentTablesData', array('tablename' => $this->tablename, 'nodeid' => $node['nodeid']));
		if (!empty($contentData))
		{
			$node += $contentData;
		}
		return $contentData;
	}

	/** This function checks to see if a node is valid, and if not it deletes or fixes it.*/
	public function checkComplete($node)
	{
		if ($this->cannotDelete)
		{
			return true;
		}
		$clean = $this->getNodeClean($node);

		if (!$clean)
		{
			$this->incompleteNodeCleanup($node);
		}
		return $clean;
	}

	/** Whether this type can be deleted. Infractions, for example, cannot be.
	 *
	 *	@return 	boolean
	 */
	public function getCannotDelete()
	{
		return $this->cannotDelete;
	}

	/** Checks to see if the node has all the required data.
	 *
	 * 	@param integer	the nodeid to be checked
	 *
	 * @return	bool
	 */
	protected function getNodeClean($node)
	{
		if ($this->contenttypeid != $node['contenttypeid'])
		{
			// Skip the check if the node doesn't match current API's type (VBV-10659)
			return true;
		}

		//Each table should have a tablename_node field. If missing, we have a problem.
		foreach((array)$this->tablename AS $table)
		{
			if (empty($node[$table . "_nodeid"]))
			{
				return false;
				break;
			}
		}
		return true;
	}

	/** This cleans up for a node that was found to be incomplete by deleting the child nodes and subsidiary table records.  It is often overridden in child classes.
	 *
	 *	@param	mixed	node record, which may have missing child table data.
	 * @return bool     Whether the node has been cleaned up
	 */
	protected function incompleteNodeCleanup($node)
	{
		if ($this->contenttypeid != $node['contenttypeid'])
		{
			// Skip the check if the node doesn't match current API's type (VBV-10659)
			return false;
		}

		$children = $this->assertor->assertQuery('vBForum:node', array('parentid' => $node['nodeid']));

		if ($children->valid())
		{
			return false;
		}

		//we can't use the normal delete functions for the damaged node because they might fail
		$params = array(vB_db_Query::TYPE_KEY =>vB_dB_Query::QUERY_DELETE, 'nodeid' => $node['nodeid']);
		foreach((array)$this->tablename AS $table)
		{
			$this->assertor->assertQuery('vBForum:' . $table, $params);
		}
		vB_Library:: instance('node')->deleteNode($node['nodeid']);
		vB_Api::instance('search')->purgeCacheForCurrentUser();
		//These records should be gone already, but just to be sure.
		$this->assertor->assertQuery('vBForum:closure', array(vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_DELETE, 'child' => $node['nodeid']));
		vB_Cache::instance()->allCacheEvent(array('nodeChg_' . $node['parentid'], 'nodeChg_' . $node['nodeid']));
		vB_Library::instance('node')->clearCacheEvents($node['nodeid']);

		return true;
	}

	/*** Processing after a move. In this case, set the text/total counts and "last" data.
	 *
	 * 	@param	int		the nodeid
	 * 	@param	int		the old parentid
	 * 	@param	int		the new parentid
	 *	@param 	array 	old node information. Used to do some checks after node moving like status changes, e.g. node changed from being approved to unapproved
	 *
	 */
	public function afterMove($nodeid, $oldparent, $newparent, $oldnode)
	{
		//We add the various counts to the new parent, and subtract from the old.
		$node = $this->nodeLibrary->getNodeBare($nodeid);

		//node might changed status let's check and update counts if needed
		$statChg = 0;
		$chgFlag = false;
		$childs = array();
		if (($node['showapproved'] != $oldnode['showapproved'])
			OR ($node['showpublished'] != $oldnode['showpublished'])
		)
		{
			$chgFlag = true;
			$statChg |= (($node['showapproved'] != $oldnode['showapproved']) AND !empty($oldnode['showapproved']) ? 1 : 0);
			$statChg |= ((($node['showpublished'] != $oldnode['showpublished'])) AND !empty($oldnode['showpublished']) ? 2 : 0);

			// fix node and children count
			$childs = $this->nodeLibrary->getChildren(array($node['nodeid']));
			foreach ($childs AS $child)
			{
				vB_Node::fixNodeCount($child['child']);
			}
		}

		if ($chgFlag AND $statChg) // changed unpub
		{
			$nparentunpub = 1;
			$oparentunpub = 0;
		}
		else if ($chgFlag) // changed pub
		{
			$nparentunpub = 0;
			$oparentunpub = 1;
		}
		else
		{
			$published = $this->isPublished($node);
			$approved = $this->isApproved($node);
			$nparentunpub = (!$published OR !$approved) ? 1 : 0;
			$oparentunpub = (!$published OR !$approved) ? 1 : 0;
		}

		$this->assertor->assertQuery('vBForum:UpdateParentCount',
		array(vB_dB_Query::TYPE_KEY =>vB_dB_Query::QUERY_STORED,
		'nodeid' => $oldparent,
		'textChange' => (-1 * !$oparentunpub), 'textUnpubChange' => (-1 * $oparentunpub)));

		$this->assertor->assertQuery('vBForum:UpdateParentCount',
		array(vB_dB_Query::TYPE_KEY =>vB_dB_Query::QUERY_STORED,
		'nodeid' => $newparent,
		'textChange' => (1 * !$nparentunpub), 'textUnpubChange' => (1 * $nparentunpub)));

		$oldParentAncestors = $newParentAncestors = array();

		// Fetch ancestors
		// fetchClosureParent() cannot be used here as it does not return the contenttypeid.
		$parentsInfo = $this->nodeLibrary->getNodes(array($oldparent, $newparent));
		$oldAncestors = array(
			array('child' => $oldparent, 'parent' => $oldparent, 'depth' => 0, 'contenttypeid' => $parentsInfo[$oldparent]['contenttypeid'])
		);

		$newAncestors = array(
			array('child' => $newparent, 'parent' => $newparent, 'depth' => 0, 'contenttypeid' => $parentsInfo[$newparent]['contenttypeid'])
		);

		$oldAncestors += $this->nodeLibrary->getParents($oldparent);
		$newAncestors += $this->nodeLibrary->getParents($newparent);
		$ancestors = array_merge($oldAncestors, $newAncestors);

		$toUpdate = array();
		if ($ancestors)
		{
			foreach($ancestors AS $ancestor)
			{
				if ($ancestor['child'] == $oldparent)
				{
					if (array_key_exists($ancestor['parent'], $newParentAncestors) AND !$chgFlag)
					{
						// common ancestor, remove it
						unset($newParentAncestors[$ancestor['parent']]);
					}
					else
					{
						$oldParentAncestors[$ancestor['parent']] = $ancestor['parent'];
					}
				}
				else if ($ancestor['child'] == $newparent)
				{
					if (array_key_exists($ancestor['parent'], $oldParentAncestors) AND !$chgFlag)
					{
						// common ancestor, remove it
						unset($oldParentAncestors[$ancestor['parent']]);
					}
					else
					{
						$newParentAncestors[$ancestor['parent']] = $ancestor['parent'];
					}
				}

				$toUpdate[$ancestor['parent']] = array('nodeid' => $ancestor['parent'], 'contenttypeid' => $ancestor['contenttypeid']);
			}
		}

		// append childs toupdate if any
		foreach ($childs as $child)
		{
			$toUpdate[$child['child']] = array('nodeid' => $child['child'], 'contenttypeid' => $child['contenttypeid']);
		}
		krsort($toUpdate);

		if (!empty($oldParentAncestors))
		{
			$this->assertor->assertQuery('vBForum:UpdateAncestorCount',
				array(vB_dB_Query::TYPE_KEY =>vB_dB_Query::QUERY_STORED,
				'nodeid' => $oldParentAncestors,
				'totalChange' => (-1 * ($node['totalcount'] + (1 * !$oparentunpub))), 'totalUnpubChange' => (-1 * ($node['totalunpubcount'] + (1 * $oparentunpub)))
				)
			);
		}

		if (!empty($newParentAncestors))
		{
			if ($chgFlag)
			{
				// get the new node information if node changed from unpub to pub so we can safely use totalcount fields
				$node = $this->nodeLibrary->getNodeBare($nodeid);
			}

			$this->assertor->assertQuery('vBForum:UpdateAncestorCount',
				array(vB_dB_Query::TYPE_KEY =>vB_dB_Query::QUERY_STORED,
					'nodeid' => $newParentAncestors,
					'totalChange' => ($node['totalcount'] + (1 * !$nparentunpub)), 'totalUnpubChange' => ($node['totalunpubcount'] + (1 * $nparentunpub))
				)
			);
		}

		// reset last content for all parents that have the deleted node
		$this->assertor->update('vBForum:node',
				array(
					'lastcontent' => 0,
					'lastcontentid' => 0,
					'lastcontentauthor' => '',
					'lastauthorid' => 0),
				array(
					'nodeid' => $oldparent
				)
		);

		// and update each ancestor last data
		$channelTypeId = vB_Types::instance()->getContentTypeID('vBForum_Channel');
		$searchApi = vB_Api::instanceInternal('Search');
		foreach ($toUpdate AS $node)
		{
			if ($node['contenttypeid'] == $channelTypeId)
			{
				$this->assertor->assertQuery('vBForum:fixNodeLast', array('nodeid' => $node['nodeid']));
			}
			else
			{
				$this->assertor->assertQuery('vBForum:updateLastData', array('parentid' => $node['nodeid'], 'timenow' => vB::getRequest()->getTimeNow()));
			}
			$searchApi->attributeChanged($node['nodeid']);
		}
	}
}


