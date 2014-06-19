<?php if (!defined('VB_ENTRY')) die('Access denied.');

class Api_Interface_Light extends Api_Interface_Collapsed
{
	/**  This enables a light session. The main issue is that we skip testing control panel, last activity, and shutdown queries.
	 *
	 *
	 */
	public function init()
	{
		if ($this->initialized)
		{
			return true;
		}

		//initialize core
		$core_path = vB5_Config::instance()->core_path;
		require_once($core_path . '/vb/vb.php');
		vB::init();

		$request = new vB_Request_WebApi();
		vB::setRequest($request);
		$config = vB5_Config::instance();
		$cookiePrefix = $config->cookie_prefix;

		if (empty($_COOKIE[$cookiePrefix . 'sessionhash']))
		{
			$sessionhash = false;
		}
		else
		{
			$sessionhash = $_COOKIE[$cookiePrefix . 'sessionhash'];
		}

		if (empty($_COOKIE[$cookiePrefix . 'cpsession']))
		{
			$cphash = false;
		}
		else
		{
			$cphash = $_COOKIE[$cookiePrefix . 'cpsession'];
		}

		if (empty($_COOKIE[$cookiePrefix . 'languageid']))
		{
			$languageid = 0;
		}
		else
		{
			$languageid = $_COOKIE[$cookiePrefix . 'languageid'];
        }

		vB_Api_Session::startSessionLight($sessionhash, $cphash, $languageid);
		$this->initialized = true;
	}

}
