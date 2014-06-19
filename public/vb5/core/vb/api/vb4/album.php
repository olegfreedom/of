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
 * vB_Api_Vb4_album
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_album extends vB_Api
{
	public function updatealbum($description, $title, $albumtype, $albumid = null)
	{
		$cleaner = vB::getCleaner();
		$description = $cleaner->clean($description, vB_Cleaner::TYPE_STR);
		$title = $cleaner->clean($title, vB_Cleaner::TYPE_STR);
		$albumtype = $cleaner->clean($albumtype, vB_Cleaner::TYPE_STR);
		$albumid = $cleaner->clean($albumid, vB_Cleaner::TYPE_UINT);

		// TODO: Implement when vB5 is more well defined on this feature.

		$result = array();
		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		return array(
			'response' => array(
				'errormessage' => 'album_added_edited',
			),
		);
	}

	public function user($pagenumber = 1, $userid = null)
	{
		$cleaner = vB::getCleaner();
		$pagenumber = $cleaner->clean($pagenumber, vB_Cleaner::TYPE_UINT);
		$userid = $cleaner->clean($userid, vB_Cleaner::TYPE_UINT);

		if ($userid < 1)
		{
			$userinfo = $loggedUser = vB_Api::instance('user')->fetchUserinfo();
			$userid = $userinfo['userid'];
		}
		else
		{
			$userinfo = vB_Api::instance('user')->fetchUserinfo($userid);
			$loggedUser = vB_Api::instance('user')->fetchUserinfo();
		}

		$result = vB_Api::instance('profile')->fetchMedia(array('userId' => $userid), $pagenumber);
		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		$albumbits = array();

		// TODO: Implement when vB5 is more well defined on this feature.

		// Setting the $show params
		$isOwner = ($userinfo['userid'] == $loggedUser['userid']) ? 1 : 0;
		// This is the way moderated is resolved in vb4.
		// 
		// 'canmoderatepictures' is not used in vb5
		// $album['moderation'] is determined by the User profile: Album options -> Picture Moderation (albums_pictures_moderation)
		// 
		// if ($album['moderation'] AND (can_moderate(0, 'canmoderatepictures') OR $vbulletin->userinfo['userid'] == $album['userid']))
		 

		return array(
			'response' => array(
				'userinfo' => vB_Library::instance('vb4_functions')->filterUserInfo($userinfo),
				'albumbits' => $albumbits,
			),
			'show' => array(
				'add_album_option' => $isOwner,
				'personalalbum' => 0, //TODO: Change this when VBV-9148 is fixed. There is no such thing as private albums in vb5 atm.
				'moderated' => 0, // TODO: Change this when moderation of album pictures is respected
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
