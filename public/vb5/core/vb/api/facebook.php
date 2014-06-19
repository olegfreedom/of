<?php if(!defined('VB_ENTRY')) die('Access denied.');

/**
 * vB_Api_Facebook
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Facebook extends vB_Api
{
	protected $disableFalseReturnOnly = array('isFacebookEnabled', 'userIsLoggedIn', 'getLoggedInFbUserId');
	protected function __construct()
	{
		parent::__construct();
	}

	public function isFacebookEnabled()
	{
		return vB_Facebook::isFacebookEnabled();
	}

	public function userIsLoggedIn($ping = false)
	{
		return vB_Facebook::instance()->userIsLoggedIn($ping);
	}

	public function getLoggedInFbUserId()
	{
		return vB_Facebook::instance()->getLoggedInFbUserId();
	}

	public function getVbUseridFromFbUserid()
	{
		return vB_Facebook::instance()->getVbUseridFromFbUserid();
	}

	public function getFbProfileUrl()
	{
		return vB_Facebook::getFbProfileUrl();
	}

	public function getFbProfilePicUrl()
	{
		return vB_Facebook::getFbProfilePicUrl();
	}

	public function getFbUserInfo()
	{
		return vB_Facebook::instance()->getFbUserInfo();
	}

}
