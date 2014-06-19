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

class vB5_Route_Legacy_vBCms extends vB5_Route_Legacy_Node
{
	protected $prefix = 'content.php';

	protected $oldcontenttypeid = array(-1);

	protected function getNewRouteInfo()
	{
		$argument = & $this->arguments;
		$param = & $this->queryParameters;

		// try to get idkey from saved info, guess it if failed
		if (isset($argument['requestvar']))
		{
			$this->idkey[] = $argument['requestvar'];
		}
		else
		{
			$this->idkey = array_keys($param);
		}

		$assertor = vB::getDbAssertor();
		$packageId = $assertor->getField('package', array('class' => 'vBCms'));
		$contentTypes = $assertor->assertQuery('vBForum:contenttype', array('packageid' => $packageId));
		foreach($contentTypes AS $contentType)
		{
			$this->oldcontenttypeid[] = intval($contentType['contenttypeid']);
		}
		return parent::getNewRouteInfo();
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
