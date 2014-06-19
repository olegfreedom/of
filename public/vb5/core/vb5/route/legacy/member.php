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

/**
 * member legacy route inherited node legacy route's way of handling routes
 * but technically not a node, so when changing super class, we may need to
 * take a look at here to make sure it will not break this
 */
class vB5_Route_Legacy_Member extends vB5_Route_Legacy_Node
{
	protected $idkey = array('u', 'userid');

	protected $prefix = 'member.php';
	
	protected function getNewRouteInfo()
	{
		$oldid = $this->captureOldId();
		$this->arguments['userid'] = $oldid;
		return 'profile';
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
