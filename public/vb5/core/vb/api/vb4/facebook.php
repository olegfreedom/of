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
 * vB_Api_Vb4_register
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_facebook extends vB_Api
{
	/**
	 * Returns list of vbUser info about the list of facebook user ids
	 * @param  [string] $facebookidList [Comma separated list of Facebook user ids]
	 * @return [array]  $usersArray     [Array of the userInfo for the required userids]
	 */
	public function getVbfromfacebook($facebookidList)
	{
		$cleaner = vB::getCleaner();
		$facebookidList = $cleaner->clean($facebookidList, vB_Cleaner::TYPE_STR);
		$usersArray = array();

		$listIds = explode(',', $facebookidList);
		$users = vB::getDbAssertor()->getRows('user', array('fbuserid' => $listIds));
		
		if (!empty($users) || !isset($users['errors']))
		{
			foreach ($users as $user) 
			{
				$usersArray[] = array(
					'userid' 	=> $user['userid'],
					'username'	=> $user['username'],
					'fbuserid'	=> $user['fbuserid'],
				);
			}
		}

		return $usersArray;
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # SVN: $Revision$
|| ####################################################################
\*======================================================================*/
