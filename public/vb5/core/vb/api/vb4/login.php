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
class vB_Api_Vb4_login extends vB_Api
{
	/**
	 * Login with fabecook logged user
	 * 
	 * @param  [string] $signed_request [fb info]
	 * @return [array]                  [response -> errormessage and session params]
	 */
	public function facebook($signed_request)
	{
		$cleaner = vB::getCleaner();
		$signed_request = $cleaner->clean($signed_request, vB_Cleaner::TYPE_STR);

		$fbUserid = vB_Api::instance('facebook')->getUser();
	
		// get vbid from logged in fb user
		$vbUserId = vB_Api::instance('facebook')->getVbUseridFromFbUserid($fbUserid);

		if (empty($vbUserId))
		{
			return array('response' => array('errormessage' => array('badlogin_facebook')));
		}
	
		$username = vB_Api::instance('user')->fetchUserName($vbUserId);
		$loginInfo = vB_Api::instance('user')->login($username, '', '', '', 'fbauto');

		if (empty($loginInfo) || isset($loginInfo['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($loginInfo);
		}

		return array(
			'session' => array(
				'dbsessionhash' => $loginInfo['sessionhash'],
				'userid' => $loginInfo['userid'],
			),
			'response' => array(
				'errormessage' => array('redirect_login')
			),
		);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # SVN: $Revision$
|| ####################################################################
\*======================================================================*/