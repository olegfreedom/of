<?php if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

/**
 * vB_Api_Vb4_subscription
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_subscription extends vB_Api
{
	// Top channels of the board
	protected $topChannels = array();

	public function viewsubscription($searchType = 0)
	{
		$userinfo = vB_Api::instance('user')->fetchUserinfo();
		if ($userinfo['userid'] <= 0)
		{
			return array('response' => array('errormessage' => 'nopermission_loggedout'));
		}

		//init cleaner
		$cleaner = vB::getCleaner();

		//clean $_REQUEST params
		$searchType = $cleaner->clean($searchType, vB_Cleaner::TYPE_STR);

		//get node id for forums
		$top = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();
		$forumid = $top['forum'];
		//get node id for blogs
		$blogTopChannel = vB_Api::instance('blog')->getBlogChannel();

		//getFollowingContent based on $_REQUEST param searchType 
		if($searchType=="forum"){
			//filter using $forumid
			$subscribed = vB_Api::instance('follow')->getFollowingContent(
				$userinfo['userid'],
				vB_Api_Follow::FOLLOWTYPE_ALL,
				array(vB_Api_Follow::FOLLOWFILTERTYPE_SORT => vB_Api_Follow::FOLLOWFILTER_SORTALL),
				null,
				array('parentid' => $forumid)
			);
		}
		else if($searchType=="blog"){
			//filter using $blogTopChannel
			$subscribed = vB_Api::instance('follow')->getFollowingContent(
				$userinfo['userid'],
				vB_Api_Follow::FOLLOWTYPE_ALL,
				array(vB_Api_Follow::FOLLOWFILTERTYPE_SORT => vB_Api_Follow::FOLLOWFILTER_SORTALL),
				null,
				array('parentid' => $blogTopChannel)
			);
		}
		else {
			//no filter, returns all relevent content
			$subscribed = vB_Api::instance('follow')->getFollowingContent(
				$userinfo['userid'],
				vB_Api_Follow::FOLLOWTYPE_ALL,
				array(vB_Api_Follow::FOLLOWFILTERTYPE_SORT => vB_Api_Follow::FOLLOWFILTER_SORTALL),
				null
			);
		}

		if (!empty($subscribed['errors']))
		{
			return array('response' => array('errormessage' => 'invalidid'));
		}

		$nodes = array();
		foreach (array_keys($subscribed['nodes']) as $nodeid)
		{
			$node = vB_Api::instance('node')->getFullContentforNodes(array($nodeid));
			$nodes[] = $node[0];
		}

		$processedbit = array();
		$subscribedbits = array();

		foreach ($nodes as $node)
		{
			if (empty($node['content']))
			{
				$conversation = $node;
			}
			else
			{
				$conversation = $node['content'];
			}	

			$itemSection = $this->getSection($node);
			if ($itemSection == 'album' OR $itemSection == 'socialgroup')
			{
				continue;
			}
			$activityType = $this->getActivityType($itemSection, $node);

			$processedbit['subscribed'] = array(
				'posttime' => vbdate('h:i A', $conversation['publishdate']),
				'postdate' => vbdate('m-d-Y', $conversation['publishdate']),
				'section'  => $itemSection,
				'type'	   => $activityType,
				'score'	   => 0.000,
			);

			switch ($itemSection) {
				case 'forum':
					$isPost = false;

					if ($node['nodeid'] == $node['starter'])
					{
						$forumInfo  = vB_Library::instance('node')->getNodeBare($node['parentid']);
						$threadInfo = $node;

						$forumid  =	(int)$node['parentid'];
						$threadid = $postid = (int)$node['nodeid'];
						$threadPreview = $postPreview = $node['content']['rawtext'];
					}
					else
					{
						$threadInfo = vB_Library::instance('node')->getNode($node['starter']);
						$forumInfo  = vB_Library::instance('node')->getNodeBare($threadInfo['parentid']);

						$forumid  =	(int)$forumInfo['nodeid'];
						$threadid = (int)$threadInfo['nodeid'];
						$postid   = (int)$node['nodeid'];
						$threadPreview = $threadInfo['content']['rawtext'];
						$postPreview   = $node['content']['rawtext'];
						$isPost = true;
					}

					$processedbit['foruminfo'] = array(
						'forumid' => $forumid,
						'title'   => vB_String::unHtmlSpecialChars($forumInfo['title']),
					);

					$processedbit['threadinfo'] = array(
						'title'		 => vB_String::unHtmlSpecialChars($threadInfo['title']),
						'forumid'	 => $forumid,
						'replycount' => $threadInfo['totalcount'],
						'views'		 => $threadInfo['totalcount'] + $threadInfo['votes'],
						'preview' => vB_Library::instance('vb4_functions')->getPreview($threadPreview),
					);

					if ($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Poll'))
					{
						$processedbit['threadinfo']['pollid'] = $node['nodeid'];
					}
					else
					{
						$processedbit['threadinfo']['threadid'] = $threadid;
					}

					if ($isPost)
					{
						$processedbit['postinfo'] = array(
							'postid'   => $postid,
							'threadid' => $threadid,
							'preview' => vB_Library::instance('vb4_functions')->getPreview($postPreview),
						);
					}

					$processedbit['show'] = array(
						'threadcontent' => true,
					);				
					break;
				
				case 'socialgroup':
					break;
				
				case 'blog':
					$topChannels = $this->getTopChannels();
					$blogComment = false;

					if ($node['parentid'] == $topChannels['blogs'])
					{
						// if it is a blog dont include it
						//break;
					}
					elseif ($node['nodeid'] == $node['starter'])
					{
						// blog entry
						$blogentryInfo  = $node;
						$blogInfo  		= vB_Library::instance('node')->getNodeBare($node['parentid']);
					}
					else
					{
						// blog comment
						$blogentryInfo  = vB_Library::instance('node')->getNode($node['parentid']);
						$blogInfo  		= vB_Library::instance('node')->getNodeBare($blogentryInfo['parentid']);
						$blogComment = true;
					}

					$processedbit['bloginfo'] = array(
						'blogid' => $blogentryInfo['nodeid'],
						'title'  => vB_String::unHtmlSpecialChars($blogentryInfo['title']),
						'blog_title' => vB_String::unHtmlSpecialChars($blogInfo['title']),
						'comments_visible' => $blogInfo['textcount'],
						'views' => $blogInfo['totalcount'] + $blogInfo['votes'],
						'preview' => vB_Library::instance('vb4_functions')->getPreview($blogentryInfo['content']['rawtext']),
					);

					if ($blogComment)
					{
						$processedbit['blogtextinfo'] = array(
							'blogtextid' => $node['nodeid'],
							'preview' => vB_Library::instance('vb4_functions')->getPreview($node['content']['rawtext']),
						);
					}
					break;

				case 'album':
					break;

				default:
					break;
			}

			$subscribedbits[] = $processedbit;
			unset($processedbit);
		}

		$out = array(
			'subscriptionbits' => $subscribedbits
		);
		return $out;
	}

	private function getSection($node) 
	{
		$section = '';

		$topChannels = $this->getTopChannels();

		if (in_array($topChannels['forum'], $node['parents']))
		{
			$section = 'forum';
		}
		else if (in_array($topChannels['blog'], $node['parents']))
		{
			$section = 'blog';
		}
		else if (in_array($topChannels['groups'], $node['parents']))
		{
			$section = 'socialgroup';
		}
		else if (in_array($topChannels['special'], $node['parents']))
		{
			$section = 'album';
		}

		if (empty($section))
		{
			// @TODO: This must not happen. Or this should throw an error.
		}

		return $section;
	}

	/**
	 * Returns the top channels
	 * 
	 * @return [array] [Top channels array]
	 */
	private function getTopChannels()
	{
		if (empty($this->topChannels))
		{
			$this->topChannels = vB_Api::instance('content_channel')->fetchTopLevelChannelIds();
		}

		return $this->topChannels;
	}


	/**
	 * Gets the activity type for that section
	 * 
	 * @param  [string] $section 
	 * @param  [array]  $node    
	 * @return [string]     
	 */
	private function getActivityType($section, $node)
	{
		$type = '';

		switch ($section) {
		case 'forum':
			if (!empty($node['setfor']))
			{
				// @TODO: This will not happen. In vb5 in order to get visitor messages you need to pass a flag, 
				// so we probably want to set this as filter
				$type = 'visitormessage';
			}
			elseif ($node['nodeid'] == $node['starter'])
			{
				$type = 'thread';
			}
			else
			{
				$type = 'post';
			}	
			break;
		case 'blog':
			if ($node['nodeid'] == $node['starter'])
			{
				$type = 'entry';
			}
			else
			{
				$type = 'comment';
			}	
			break;
		case 'socialgroup':
			$topChannels = $this->getTopChannels();

			if ($topChannels['groups'] == $node['parentid'])
			{
				$type = 'group';
			}
			elseif ($node['nodeid'] == $node['starter'])
			{
				$type = 'discussion';
			}
			else
			{
				$type = 'groupmessage';
			}
			break;
		case 'album':
			// The other options (comment and album) are not applicable
			$type = 'photo';
			break;
		default:
			// @TODO: This should not happen or it should throw an error
			break;
		}

		return $type;
	}


	public function removesubscription($threadid = "", $forumid = "")
	{
		$userinfo = vB_Api::instance('user')->fetchUserinfo();
		if ($userinfo['userid'] <= 0)
		{
			return array('response' => array('errormessage' => 'nopermission_loggedout'));
		}

		$cleaner = vB::getCleaner();
		$threadid = $cleaner->clean($threadid, vB_Cleaner::TYPE_UINT);
		$forumid = $cleaner->clean($forumid, vB_Cleaner::TYPE_UINT);

		if ($threadid > 0)
		{
			$nodeid = $threadid;
		}
		else if ($forumid > 0)
		{
			$nodeid = $forumid;
		}
		else
		{
			return array('response' => array('errormessage' => 'invalidid'));
		}

		$success = vB_Api::instance('follow')->delete($nodeid, vB_Api_Follow::FOLLOWTYPE_CONTENT);
		if (!empty($success['errors']))
		{
			return array('response' => array('errormessage' => 'invalidid'));
		}
		return array('response' => array('errormessage' => 'redirect_subsremove_thread'));
		return null;
	}

	public function addsubscription()
	{
		return array('response' => array('HTML' => array('emailselected' => array(0))));
	}

	public function doaddsubscription($threadid = "", $forumid = "")
	{
		$userinfo = vB_Api::instance('user')->fetchUserinfo();
		if ($userinfo['userid'] <= 0)
		{
			return array('response' => array('errormessage' => 'nopermission_loggedout'));
		}

		$cleaner = vB::getCleaner();
		$threadid = $cleaner->clean($threadid, vB_Cleaner::TYPE_UINT);
		$forumid = $cleaner->clean($forumid, vB_Cleaner::TYPE_UINT);

		if ($threadid > 0)
		{
			$nodeid = $threadid;
		}
		else if ($forumid > 0)
		{
			$nodeid = $forumid;
		}
		else
		{
			return array('response' => array('errormessage' => 'invalidid'));
		}

		$success = vB_Api::instance('follow')->add($nodeid, vB_Api_Follow::FOLLOWTYPE_CONTENT, $userinfo['userid']);
		if (!empty($success['errors']))
		{
			return array('response' => array('errormessage' => 'invalidid'));
		}
		return array('response' => array('errormessage' => 'redirect_subsadd_thread'));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # SVN: $Revision$
|| ####################################################################
\*======================================================================*/
