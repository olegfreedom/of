<?php if(!defined('VB_ENTRY')) die('Access denied.');
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

class vB5_Route_Page extends vB5_Route
{
	protected $page = null;

	protected function getPage()
	{
		if ($this->page === NULL AND isset($this->arguments['pageid']))
		{
			$cache = vB_Cache::instance(vB_Cache::CACHE_FAST);
			$hashkey = 'vbPage_' . $this->arguments['pageid'];
			$this->page = $cache->read($hashkey);
			if (empty($this->page))
			{
				$this->page = vB::getDbAssertor()->getRow('page', array('pageid' => intval($this->arguments['pageid'])));
				$cache->write($hashkey, $this->page, 86400, 'vbPageChg_' . $this->arguments['pageid']);
			}

		}

		return $this->page;
	}

	public function __construct($routeInfo, $matches, $queryString = '', $anchor = '')
	{
		parent::__construct($routeInfo, $matches, $queryString, $anchor);
		if (isset($this->arguments['pageid']) AND !empty($this->arguments['pageid']))
		{
			$this->setPageKey('pageid');

			$page = $this->getPage();
			if ($page)
			{
				switch($page['guid'])
				{
					case vB_Page::PAGE_SOCIALGROUP:
						$this->checkStyle(vB_Channel::DEFAULT_SOCIALGROUP_PARENT);
						break;
					case vB_Page::PAGE_BLOG:
						$this->setUserAction('viewing_blog_home');
						$this->checkStyle(vB_Channel::DEFAULT_BLOG_PARENT);
					break;
					case vB_Page::PAGE_ONLINE:
					case vB_Page::PAGE_MEMBERLIST:
						$this->setUserAction('viewing_whos_online');
						break;
					case vB_Page::PAGE_SEARCH:
					case vB_Page::PAGE_SEARCHRESULT:
						$this->setUserAction('searching_forums');
						break;
					case vB_Page::PAGE_HOME:
					default:
						// TODO: should pages have a link by default?
						$this->setUserAction('viewing_x', $page['title'], $this->getFullUrl('nosession|fullurl'));
				}
			}
		}
	}

	protected function checkStyle($channelguid)
	{
		$channel = vB_Api::instanceInternal('content_channel')->fetchChannelByGUID($channelguid);
		if (!empty($channel['styleid']))
		{
			$forumOptions = vB::getDatastore()->getValue('bf_misc_forumoptions');
			if($forumOptions['styleoverride'] & $channel['options'])
			{
				// the channel must force the style
				$this->arguments['forceStyleId'] = $channel['styleid'];
			}
			else
			{
				// the channel suggests to use this style
				$this->arguments['routeStyleId'] = $channel['styleid'];
			}
		}
	}

	protected function setBreadcrumbs()
	{
		$page = $this->getPage();

		if ($page)
		{
			$this->breadcrumbs = array(
				0 => array(
					'title' => $page['title'],
					'url' => ''
				)
			);
		}
		else
		{
			parent::setBreadcrumbs();
		}
	}

	public function getUrl()
	{
		$url = '/' . $this->prefix;
		
		if (isset($this->arguments['pagenum']) AND is_numeric($this->arguments['pagenum']) AND $this->arguments['pagenum']>1)
		{
			$url .= '/page' . intval($this->arguments['pagenum']);
		}

		if (strtolower(vB_String::getCharset()) != 'utf-8')
		{
			$url = vB_String::encodeUtf8Url($url);
		}
		
		return $url;
	}

	public function getCanonicalRoute()
	{
		if (!isset($this->canonicalRoute) AND !empty($this->arguments['pageid']))
		{
			$page = $this->getPage();
			$data = array();

			if (isset($this->arguments['pagenum']) AND is_numeric($this->arguments['pagenum']) AND $this->arguments['pagenum']>1)
			{
				$data['pagenum'] = $this->arguments['pagenum'];
			}

			$this->canonicalRoute = self::getRoute($page['routeid'], $data, $this->queryParameters);
		}

		return $this->canonicalRoute;
	}

	protected static function validInput(array &$data)
	{
		if (
				!isset($data['contentid']) OR !is_numeric($data['contentid']) OR
				!isset($data['prefix'])
			)
		{
			return FALSE;
		}

		$data['regex'] = $data['prefix'];
		$data['class'] = __CLASS__;

		/************************************************************************************************************/
		/** TODO: This class is being used for search routes since rev 58051 (fix for VBV-176) which doesn't
		 * seem correct since they use a different controller and action.
		 *  This is a temporal fix to prevent overwriting the controller and action, but eventually we'll need
		 * to use a different route class. I prefer the route class to always set the controller and action.
		 */
		if (!isset($data['controller']))
		{
			$data['controller']	= 'page';
		}
		if (!isset($data['action']))
		{
			$data['action']	= 'index';
		}
		/************************************************************************************************************/

		$data['arguments']	= serialize(array('pageid' => $data['contentid']));

		return parent::validInput($data);
	}

	protected static function updateContentRoute($oldRouteInfo, $newRouteInfo)
	{
		$db = vB::getDbAssertor();
		$events = array();

		// update redirect301 fields
		$updateIds = $db->assertQuery('get_update_route_301', array('oldrouteid' => $oldRouteInfo['routeid']));
		
		if (!empty($updateIds))
		{
			$routeIds = array();
			foreach($updateIds AS $route)
			{
				$routeid = $route['routeid'];
				$events[] = "routeChg_$routeid";
				$routeIds[] = $routeid;
			}
			
			$db->update('routenew', array('redirect301' => $newRouteInfo['routeid'], 'name' => ''), array('routeid' => $routeIds));
		}

		// don't modify the routeid for default pages, as it will still be used
		$updateIds = $db->assertQuery('page', array(
			vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_SELECT,
			vB_dB_Query::COLUMNS_KEY => array('pageid'),
			vB_dB_Query::CONDITIONS_KEY => array('routeid' => $oldRouteInfo['routeid'], 'pagetype' => vB_Page::TYPE_CUSTOM),
		));
		
		if (!empty($updateIds))
		{
			$pageIds = array();
			foreach($updateIds AS $page)
			{
				$pageid = $page['pageid'];
				$events[] = "pageChg_$pageid";
				$pageIds[] = $pageid; 
			}
			$db->update('page', array('routeid' => $newRouteInfo['routeid']), array('pageid' => $pageIds));
		}
		
		vB_Cache::allCacheEvent($events);
	}

	public static function exportArguments($arguments)
	{
		$data = unserialize($arguments);
		
		if (!empty($data['channelid']))
		{
			$channel = vB::getDbAssertor()->getRow('vBForum:channel', array('nodeid' => $data['channelid']));
			if (empty($channel))
			{
				throw new Exception('Couldn\'t find channel');
			}
			$data['channelGuid'] = $channel['guid'];
			unset($data['channelid']);
		}

		$page = vB::getDbAssertor()->getRow('page', array('pageid' => $data['pageid']));
		if (empty($page))
		{
			throw new Exception('Couldn\'t find page');
		}
		$data['pageGuid'] = $page['guid'];
		unset($data['pageid']);

		return serialize($data);
	}

	public static function importArguments($arguments)
	{
		$data = unserialize($arguments);
		
		// Some pages may have a channel associated (e.g. Groups, Blogs)
		if (!empty($data['channelGuid']))
		{
			$channel = vB::getDbAssertor()->getRow('vBForum:channel', array('guid' => $data['channelGuid']));
			if (empty($channel))
			{
				throw new Exception('Couldn\'t find channel');
			}
			$data['channelid'] = $channel['nodeid'];
			unset($data['channelGuid']);
		}

		$page = vB::getDbAssertor()->getRow('page', array('guid' => $data['pageGuid']));
		if (empty($page))
		{
			throw new Exception('Couldn\'t find page');
		}
		$data['pageid'] = $page['pageid'];
		unset($data['pageGuid']);

		return serialize($data);
	}

	public static function importContentId($arguments)
	{
		return $arguments['pageid'];
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
