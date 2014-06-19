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

class vB_Request_WebApi extends vB_Request_Web
{
	public function __construct()
	{
		parent::__construct();

		$this->sessionClass = 'vB_Session_WebApi';
	}

	public function createSession()
	{
		$args =  func_get_args();
		call_user_func_array(array('parent', 'createSession'),$args);

		return array(
			'sessionhash' => $this->session->get('sessionhash')
		);
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/