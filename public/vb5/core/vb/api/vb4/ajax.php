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
 * vB_Api_Vb4_forum
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Vb4_ajax extends vB_Api
{
	public function dismissnotice($noticeid)
	{
		$success = vB_Api::instanceInternal('notice')->dismiss($noticeid);
		return array('response' => array('dismissed' => $success));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # SVN: $Revision$
|| ####################################################################
\*======================================================================*/
