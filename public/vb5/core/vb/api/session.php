<?php if(!defined('VB_ENTRY')) die('Access denied.');

/**
 * vB_Api_Session
 *
 * @package vBApi
 * @access public
 */
class vB_Api_Session extends vB_Api
{
	/**
	 * Get basic information from a stored session
	 *
	 * @param string    session hash
     *
	 * @return mixed    array of permissions,
	 */
	public static function getInfoFromHash($sessionHash = false)
	{
        if (!empty($sessionHash))
        {
            $session = vB::getDbAssertor()->getRow('session', array('sessionhash' => $sessionHash));
        }

        if (empty($session) OR !empty($session['errors']))
        {
            //guest user
            return array('userid' => 0, 'languageid' =>  vB::getDatastore()->getOption('languageid') );
        }
        //This has userid and language.
        return array('userid' => $session['userid'], 'languageid' => $session['languageid']);
	}

	/**
	 * starts a new lightweight (no shutdown) session
	 *
	 * @param string    session hash
	 *
	 * @return mixed    array of permissions,
	 */
	public static function startSessionLight($sessionHash = false, $cphash = false, $languageid = 0)
	{
		if (!empty($sessionHash))
		{
			$sessionInfo = vB::getDbAssertor()->getRow('session', array('sessionhash' => $sessionHash));

			if (!empty($sessionInfo) AND empty($sessionInfo['errors']))
			{
				$session = vB_Session_Web::getSession($sessionInfo['userid'], $sessionHash );

				if (!empty($cphash))
				{
					$session->setCpsessionHash($cphash);
				}
			}

		}

		if (empty($session))
		{
			$session = new vB_Session_Web(vB::getDbAssertor(), vB::getDatastore(), vB::getConfig(), '', 0, '', 0, $languageid);
		}

		$session->set('languageid', $languageid);

		vB::skipShutdown(true);
		vB::setCurrentSession($session);
		return $session;
	}

	public function disableShutdownQueries()
	{
		vB::getDbAssertor()->skipShutdown();
	}

}
