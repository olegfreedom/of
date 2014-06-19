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
 * vB_Api_Vb4_activity
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_activity extends vB_Api
{
	const FILTER_SORTBY_RECENT  = 'recent';
	const FILTER_SORTBY_POPULAR = 'popular';
	const FILTER_LASTDAY 		= 'today';
	const FILTER_LASTWEEK 		= 'week';
	const FILTER_LASTMONTH 		= 'month';
	//const FILTER_SHOW_ALL  		= 'all';
	const FILTER_SHOW_SOCIALGROUP = 'socialgroup';
	const FILTER_SHOW_BLOG 		= 'blog';
	const FILTER_SHOW_CMS		= 'cms';
	const FILTER_SHOW_FORUM		= 'forum';
	const FILTER_SHOW_PHOTOS	= 'photos';

	/**
	 *  Results per page
	 */
	protected $perpage = 15;

	// Resovling the result structure block name
	protected $section = array(
		// Commented out are not yet implemented in vb5
		'album' 	  => 'albuminfo',
		//'calendar'	  => 'calendarinfo',
		'forum' 	  => 'foruminfo',
		'socialgroup' => 'groupinfo',
		//'cms' 		  => 'cmsinfo',
		'blog' 		  => 'bloginfo',
	);

	// Top channels of the board
	protected $topChannels = array();

	/**
	 * Default activity call.
	 * 
	 * @param  integer $userid      [userid]
	 * @param  integer $mindateline [The dateline of the min record currently shown]
	 * @param  integer $maxdateline [The dateline of the max record currently shown]
	 * @param  integer $minscore    []
	 * @param  mixed   $minid       [CSV of the ids of the items with mindateline]
	 * @param  string  $maxid       [CSV of the ids of the items with maxdateline]
	 * @param  string  $sortby      [Sorting the results. Possible values see constant with FILTER_SORT_BY_*]
	 * @param  string  $time        [Filtering the results. Possible values see constant with FILTER_*]
	 * @param  string  $show        [Filtering the results by section. Possible values see constant with FILTER_SHOW_*]
	 * @return array                [Result structure]
	 */
	public function call(
		$userid = 0,
		$mindateline = 0,
		$maxdateline = 0,
		$minscore = 0,
		$minid = '',
		$maxid = '',
		$sortby = '',
		$time = '',
		$show = '',
		$pagenumber = 0
	)
	{
		$cleaner = vB::getCleaner();

		$userid 	 = $cleaner->clean($userid, vB_Cleaner::TYPE_UINT); 
		$mindateline = $cleaner->clean($mindateline, vB_Cleaner::TYPE_UINT);
		$maxdateline = $cleaner->clean($maxdateline, vB_Cleaner::TYPE_UINT);
		$minscore	 = $cleaner->clean($minscore, vB_Cleaner::TYPE_NUM);
		$minid 		 = $cleaner->clean($minid, vB_Cleaner::TYPE_STR);
		$maxid 		 = $cleaner->clean($maxid, vB_Cleaner::TYPE_STR);
		$sortby 	 = $cleaner->clean($sortby, vB_Cleaner::TYPE_STR);
		$time 		 = $cleaner->clean($time, vB_Cleaner::TYPE_STR);
		$show 		 = $cleaner->clean($show, vB_Cleaner::TYPE_STR);
		$pagenumber  = $cleaner->clean($pagenumber, vB_Cleaner::TYPE_UINT);

		$usedFilter = false;

		$searchJSON = array();

		$searchJSON['view'] = vB_Api_Search::FILTER_VIEW_ACTIVITY;

		if (!empty($userid))
		{
			$searchJSON['authorid'] = $userid;
		}

		// In vb4 when you send $mindateline You send the minid(s) (when you click on show more)
		// and with that info you get older set of data for the stream
		if (!empty($mindateline))
		{
			$searchJSON['date']['to'] = $mindateline;
			$usedFilter = true;
		}

		// In vb4 when you send $maxdateline You send the maxid(s) 
		// and with that info you get any newer results that are there (if any)
		if (!empty($maxdateline))
		{
			$searchJSON['date']['from'] = $maxdateline;
			$usedFilter = true;
		}

		if (!empty($sortby))
		{
			if ($sortby == self::FILTER_SORTBY_RECENT)
			{
				$searchJSON['sort'] = array('dateline' => 'desc');
			}
			else
			{
				// Best we have in vB5 is replies, this could also be votes if desired
				$searchJSON['sort'] = array('replies' => 'desc');
				$usedFilter = true;
			}
		}

		if (!empty($time))
		{
			$usedFilter = true;
			if ($time == self::FILTER_LASTDAY)
			{
				$searchJSON['date']['from'] = vB_Api_Search::FILTER_LASTDAY;
			}
			elseif ($time == self::FILTER_LASTWEEK)
			{
				$searchJSON['date']['from'] = vB_Api_Search::FILTER_LASTWEEK;
			}
			elseif ($time == self::FILTER_LASTMONTH)
			{
				$searchJSON['date']['from'] = vB_Api_Search::FILTER_LASTMONTH;
			}
		}
		else
		{
			$searchJSON['date']['from'] = vB_Api_Search::FILTER_LASTYEAR;
		}

		if (!empty($show))
		{
			$usedFilter = true;
			$topChannels = $this->getTopChannels();

			if ($show == self::FILTER_SHOW_SOCIALGROUP)
			{
				//get the sg channel
				$searchJSON['channel'] = $topChannels['groups'];
			}
			elseif ($show == self::FILTER_SHOW_BLOG)
			{
				// get the blog channel
				$searchJSON['channel'] = $topChannels['blog'];
			}
			elseif ($show == self::FILTER_SHOW_CMS)
			{
				// This is not yet implemented in vb5
				//$searchJSON['channel'] = $topChannels['cms'];
			}
			elseif ($show == self::FILTER_SHOW_FORUM)
			{
				// get the forum channel
				$searchJSON['channel'] = $topChannels['forum'];
			}
			elseif ($show == self::FILTER_SHOW_PHOTOS)
			{
				// In vb4 this means getting the photos from the socialgroups and albums
				// NOTE: This has different result layout then the others
				$searchJSON['type'] = array('vBForum_Photo');
			}
		}

		$result = vB_Api::instance('search')->getInitialResults($searchJSON, $this->perpage, (empty($pagenumber) ? 1 : $pagenumber), true);

		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		$activitybits = $this->prepareSearchData($result, $show);

		$count = count($activitybits);

		// Where do I get the data for 'actdata' (perpage, refresh) and for 'show' -> Need to investigate how this is done in vb4 activity
		$response = array(
			'actdata' => array(
				'mindateline' => $mindateline,
				'maxdateline' => $maxdateline,
				'minscore' 	  => $minscore,
				'minid' 	  => $minid,
				'maxid' 	  => $maxid,
				'count' 	  => $count,
				'totalcount'  => $count,
				'perpage' 	  => $this->perpage,
				'time' 		  => $time,
				'sortby' 	  => $sortby,
				'refresh' 	  => 1,
			),
			'show' => array(
				'more_results' => ($count <= $this->perpage ? 0 : 1),
				'as_blog' => true,
				'as_cms' => false ,
				'as_socialgroup' => true,
				'filterbar' => $usedFilter,
			),
		);

		$response['activitybits'] = $activitybits;

		return array(
			'response' => $response,
		);
	}

	/**
	 * Structuring the result data for output
	 * 
	 * @param  [Array]  $result [Array with the search result]
	 * @param  [String] $show   [See constant FILTER_SHOW_*. Only when it has value 'photos' it changes the output structure]
	 * 
	 * @return [array]			[Formatted structure for output]
	 */
	private function prepareSearchData($result, $show)
	{
		$activitybits = array();

		foreach ($result['results'] AS $node)
		{
			$activitybit = array();

			if (empty($node['content']))
			{
				$conversation = $node;
			}
			else
			{
				$conversation = $node['content'];
			}

			$itemSection  = $this->getSection($node);
			// This is just a temp hack to not show album items. 
			// The core/attachment.php will not play along with the current passed params from the mobile app
			//
			// Socialgroup not handled in the apps
			//
			if ($itemSection == 'album' OR $itemSection == 'socialgroup')
			{
				continue;
			}
			$activityType = $this->getActivityType($itemSection, $node);

			$activitybit['activity'] = array(
				'posttime' => vbdate('h:i A', $conversation['publishdate']),
				'postdate' => vbdate('m-d-Y', $conversation['publishdate']),
				'section'  => $itemSection,
				'type'	   => $activityType,
				'score'	   => 0.000,
			);

/*
			@TODO: Disabling this for now. As noted in other places of this class the core/attachment.php wont serve the photos atm.

			if ($show == self::FILTER_SHOW_PHOTOS)
			{
				$activitybit['attachmentinfo'] = array(
					'attachmentid'  => $conversation['nodeid'],
					'dateline' 		=> $conversation['publishdate'],
					'thumbnail_width'  => '',
					'thumbnail_height' => '',
				);

				if ($itemSection == 'socialgroup')
				{
					$activitybit['groupinfo'] = array(
						'albumid' => $conversation['parentid'],
					);
				}
				else
				{
					$activitybit['albuminfo'] = array(
						'albumid' => $conversation['parentid'],
					);
				}
			}
			else

 */
			{
				// Section specific info
				switch ($itemSection) {
				case 'forum':
					// @TODO: This is inside the special channel and not here, but at this moment the serach api doesn't even return vm's. 
					// Need to set up a filter for those or just a flag of ijf we should include or not.
					if (!empty($node['setfor']))
					{
						$activitybit['messageinfo'] = array(
							'vmid' 	  => $node['nodeid'],
							'preview' => $node['preview'],
						);

						$activitybit['userinfo2'] = array(
							'userid'   => $node['setfor'],
							'username' => vB_Library::instance('user')->fetchUserName($node['setfor']),
						);
					}
					else
					{
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

						$activitybit['foruminfo'] = array(
							'forumid' => $forumid,
							'title'   => vB_String::unHtmlSpecialChars($forumInfo['title']),
						);

						$activitybit['threadinfo'] = array(
							'title'		 => vB_String::unHtmlSpecialChars($threadInfo['title']),
							'forumid'	 => $forumid,
							'replycount' => $threadInfo['totalcount'],
							'views'		 => $threadInfo['totalcount'] + $threadInfo['votes'],
							'preview' => vB_Library::instance('vb4_functions')->getPreview($threadPreview),
						);

						if ($node['contenttypeid'] == vB_Types::instance()->getContentTypeID('vBForum_Poll'))
						{
							$activitybit['threadinfo']['pollid'] = $node['nodeid'];
						}
						else
						{
							$activitybit['threadinfo']['threadid'] = $threadid;
						}

						if ($isPost)
						{
							$activitybit['postinfo'] = array(
								'postid'   => $postid,
								'threadid' => $threadid,
								'preview' => vB_Library::instance('vb4_functions')->getPreview($postPreview),
							);
						}

						$activitybit['show'] = array(
							'threadcontent' => true,
						);
					}
					break;

				case 'blog':
					$topChannels = $this->getTopChannels();
					$blogComment = false;

					if ($node['parentid'] == $topChannels['blogs'])
					{
						// if it is a blog dont include it
						break;
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

					$activitybit['bloginfo'] = array(
						'blogid' => $blogentryInfo['nodeid'],
						'title'  => vB_String::unHtmlSpecialChars($blogentryInfo['title']),
						'blog_title' => vB_String::unHtmlSpecialChars($blogInfo['title']),
						'comments_visible' => $blogInfo['textcount'],
						'views' => $blogInfo['totalcount'] + $blogInfo['votes'],
						'preview' => vB_Library::instance('vb4_functions')->getPreview($blogentryInfo['content']['rawtext']),
					);

					if ($blogComment)
					{
						$activitybit['blogtextinfo'] = array(
							'blogtextid' => $node['nodeid'],
							'preview' => vB_Library::instance('vb4_functions')->getPreview($node['content']['rawtext']),
						);
					}
					break;

				case 'socialgroup':
					$topChannels = $this->getTopChannels();
					$discussionInfo = false;
					$messageinfo = false;

					if ($node['parentid'] == $topChannels['groups'])
					{
						// group
						$groupInfo = $node;
					}
					elseif ($node['nodeid'] == $node['starter'])
					{
						// group discussion
						$discussionInfo = $node;
						$groupInfo 		= vB_Library::instance('node')->getNodeBare($node['parentid']);
					}
					else
					{
						// group message
						$discussionInfo = vB_Library::instance('node')->getNodeBare($node['parentid']);
						$groupInfo 		= vB_Library::instance('node')->getNodeBare($discussionInfo['parentid']);
						$messageInfo 	= $node;
					}

					$activitybit['groupinfo'] = array(
						'groupid' => $groupInfo['nodeid'],
						'name'    => $groupInfo['title'],
					);

					if ($discussionInfo)
					{
						$activitybit['discussioninfo'] = array(
							'discussionid' => $discussionInfo['nodeid'],
							'title'		 => $discussionInfo['title'],
							'preview' => vB_Library::instance('vb4_functions')->getPreview($discussionInfo['content']['rawtext']),
							'visible'	 => 1,
						);
					}

					if ($messageInfo)
					{
						$activitybit['messageinfo'] = array(
							'gmid'     => $messageInfo['nodeid'],
							'preview' => vB_Library::instance('vb4_functions')->getPreview($discussionInfo['content']['rawtext']),
						);
					}

					break;

				case 'album':
					// @TODO: Until the core/attachment.php is changed to work with the current params from the mobile app 
					// or we change how the photos are fetch in the app, this code does not execute
					$albumInfo = vB_Api::instanceInternal('node')->getNodeFullContent($node['nodeid']);
					$albumInfo = $albumInfo[$node['nodeid']];

					$activitybit['albuminfo'] = array(
						'albumid' => $albumInfo['nodeid'],
						'title'		 => $albumInfo['title'],
						// Both of these are missing in the examples in wiki
						'picturecount'	 => count($albumInfo['attach']),
						'views'	 => $albumInfo['totalcount'] + $albumInfo['votes'],
					);

					$activitybit['photocount'] = count($albumInfo['attach']);

					$activitybit['attach'] = array();

					foreach ($albumInfo['attach'] as $attach) {
						$activitybit['attach'][] = array(
							'attachmentid'  => $attach['nodeid'],
							'dateline'		=> $attach['dateline'],
							'thumbnail_width'	 => $attach['width'],
							'thumbnail_height'	 => $attach['height'],
						);
					}				
					break;

				default:
					break;
				}
			}

			$userInfo = vB_Library::instance('user')->fetchUserinfo($node['userid'], array('avatar'));

			$activitybit['userinfo'] = array(
				'userid'   => $userInfo['userid'],
				'username' => $userInfo['username'],
				'avatarurl'  => vB_Library::instance('vb4_functions')->avatarUrl($userInfo['userid']),
				'showavatar' => $userInfo['showavatars'],
			);

			$activitybits[] = $activitybit;
		}

		return $activitybits;
	}

	/**
	 * Determins the section of the node. Map it to the vb4 sections
	 * 
	 * @param  [array]  $node    [The node that needs to be mapped]
	 * @return [string] $section [The section]
	 */
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
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # SVN: $Revision$
|| ####################################################################
\*======================================================================*/
