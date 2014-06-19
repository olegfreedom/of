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

class vB5_Route_Legacy_Thread extends vB5_Route_Legacy_Page
{
	protected $idkey = array('t', 'threadid');

	protected $prefix = 'showthread.php';

	// use postid if available
	protected function getNewRouteInfo()
	{
		$param = & $this->queryParameters;
		if(isset($param['p']) AND $oldid=intval($param['p']) OR isset($param['postid']) AND $oldid=intval($param['postid']))
		{
			$node = vB::getDbAssertor()->getRow('vBForum:fetchLegacyPostIds', array(
				'oldids' => $oldid,
				'postContentTypeId' => vB_Types::instance()->getContentTypeID('vBForum_Post'),
			));

			if (empty($node))
			{
				throw new vB_Exception_404('invalid_page');
			}

			$this->arguments['nodeid'] = $node['starter'];
			$this->arguments['innerPost'] = $node['nodeid'];
			return $node['routeid'];
		}
		$this->oldcontenttypeid = vB_Types::instance()->getContentTypeID(array('package' => 'vBForum', 'class' =>'Thread'));
		return parent::getNewRouteInfo();
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
