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
class vB_Api_Vb4_register extends vB_Api
{
	public function addmember(
		$agree,
		$username,
		$email,
		$emailconfirm,
		$fbname = null,
		$fbuserid = null,
		$month = null,
		$day = null,
		$year = null,
		$password = null,
		$password_md5 = null,
		$passwordconfirm = null,
		$passwordconfirm_md5 = null,
		$userfield = null)
	{
		$cleaner = vB::getCleaner();
		$agree = $cleaner->clean($agree, vB_Cleaner::TYPE_UINT);
		$month = $cleaner->clean($month, vB_Cleaner::TYPE_UINT);
		$day = $cleaner->clean($day, vB_Cleaner::TYPE_UINT);
		$fbuserid = $cleaner->clean($fbuserid, vB_Cleaner::TYPE_UINT);
		$fbname = $cleaner->clean($fbname, vB_Cleaner::TYPE_STR);
		$year = $cleaner->clean($year, vB_Cleaner::TYPE_UINT);
		$username = $cleaner->clean($username, vB_Cleaner::TYPE_STR);
		$email = $cleaner->clean($email, vB_Cleaner::TYPE_STR);
		$emailconfirm = $cleaner->clean($emailconfirm, vB_Cleaner::TYPE_STR);
		$password = $cleaner->clean($password, vB_Cleaner::TYPE_STR);
		$password_md5 = $cleaner->clean($password_md5, vB_Cleaner::TYPE_STR);
		$passwordconfirm_md5 = $cleaner->clean($passwordconfirm_md5, vB_Cleaner::TYPE_STR);
		$passwordconfirm = $cleaner->clean($passwordconfirm, vB_Cleaner::TYPE_STR);
		$userfield = $cleaner->clean($userfield, vB_Cleaner::TYPE_ARRAY);

		if (empty($agree))
		{
			return array('response' => array('errormessage' => array('register_not_agreed')));
		}

		if (empty($username) ||
			empty($email) ||
			empty($emailconfirm) ||
			empty($agree))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$check = vB_Api::instance('user')->checkUsername($username);
		if (empty($check) || isset($check['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($check);
		}

		if ((empty($password) ||
			empty($passwordconfirm)) &&
			(empty($password_md5) ||
			empty($passwordconfirm_md5)))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		if (!empty($password) && $password != $passwordconfirm)
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}
		else
		{
			$password = $password;
		}

		if (!empty($password_md5) && $password_md5 != $passwordconfirm_md5)
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}
		else
		{
			$password = $password_md5;
		}

		if ($email != $emailconfirm)
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$userdata = array('username' => $username, 'email' => $email);
		if ($year > 0 AND $month > 0 AND $day > 0)
		{
			$userdata['birthday'] = date('m-d-Y', mktime(0, 0, 0, $month, $day, $year));
		}

		if (!empty($fbname) AND !empty($fbuserid))
		{
			$userdata['fbuserid'] = $fbuserid;
			$userdata['fbname'] = $fbname;
			$userdata['fbjoindate'] = time();
		}

		$hv = vB_Library::instance('vb4_functions')->getHVToken();
		$result = vB_Api::instance('user')->save(0, $password, $userdata, array(), array(), $userinput, array(), $hv);

		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}

		return array(
			'response' => array('errormessage' => array('registration_complete')),
			'session' => array('sessionhash' => $result['dbsessionhash']),
		);
	}

	public function call()
	{
		$result = vB_Api::instance('user')->fetchProfileFieldsForRegistration(array());
		if ($result === null || isset($result['errors']))
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		$custom_fields_profile = array();
		foreach ($result['profile'] as $field)
		{
			$custom_fields_profile[] = $this->parseCustomField($field);
		}

		$custom_fields_other = array();
		foreach ($result['other'] as $field)
		{
			$custom_fields_other[] = $this->parseCustomField($field);
		}

		$custom_fields_option = array();
		foreach ($result['option'] as $field)
		{
			$custom_fields_option[] = $this->parseCustomField($field);
		}

		$result = vB_Api::instance('phrase')->fetch(array('site_terms_and_rules', 'coppa_rules_description'));
		if ($result === null || isset($result['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($result);
		}
		$forumRules = $result['site_terms_and_rules'];
		$coppaRules = $result['coppa_rules_description'];

		$options = vB::getDatastore()->getValue('options');

		$out = array(
			'vboptions' => array(
				'usecoppa' => $options['usecoppa'],
				'webmasteremail' => $options['webmasteremail'],
			),
			'vbphrase' => array(
				'forum_rules_description' => $forumRules,
				'coppa_rules_description' => $coppaRules,
			),
			'response' => array(
				'customfields_other' => $custom_fields_other,
				'customfields_profile' => $custom_fields_profile,
				'customfields_option' => $custom_fields_option,
			),
		);
		return $out;
	}

	private function parseCustomField($data)
	{
		$field = array(
			'custom_field_holder' => array(
				'profilefield' => array(
					'type' => $data['type'],
					'title' => $data['title'],
					'description' => $data['description'],
					'currentvalue' => $data['currentvalue'],
				),
				'profilefieldname' => $data['fieldname'],
			),
		);

		if ($data['type'] == 'select' || $data['type'] == 'select_multiple')
		{
			$selectbits = array();
			foreach ($data['bits'] as $key => $bit)
			{
				$selectbits[] = array(
					'key' => $key,
					'val' => $bit['val'],
					'selected' => '',
				);
			}
			$field['custom_field_holder']['selectbits'] = $selectbits;
		}

		if ($data['type'] == 'radio' || $data['type'] == 'checkbox')
		{
			$radiobits = array();
			foreach ($data['bits'] as $key => $bit)
			{
				$radiobits[] = array(
					'key' => $key,
					'val' => $bit['val'],
					'checked' => '',
				);
			}
			$field['custom_field_holder']['radiobits'] = $radiobits;
		}

		return $field;
	}

	/**
	 * Connect loged in user to facebook account
	 * 
	 * @param  [int] 	$link
	 * @param  [int] 	$fbuserid       Facebook userid
	 * @param  [string] $fbname         Facebook username
	 * @param  [type] 	$signed_request Facebook response
	 * @return [array]
	 */
	public function fbconnect($link, $fbuserid, $fbname, $signed_request)
	{
		$cleaner = vB::getCleaner();

		// Clean the input params
		$link 			= $cleaner->clean($link, vB_Cleaner::TYPE_UINT);
		$fbuserid 		= $cleaner->clean($fbuserid, vB_Cleaner::TYPE_UINT);
		$fbname 		= $cleaner->clean($fbname, vB_Cleaner::TYPE_STR);
		$signed_request = $cleaner->clean($signed_request, vB_Cleaner::TYPE_STR);

		if (!$link)
		{
			return array('response' => array('errormessage' => array('invalidid')));
		}

		if (!vB_Api::instance('facebook')->isFacebookEnabled())
		{
			return array('response' => array('errormessage' => array('facebook_disabled')));
		}

		$user = vB_Api::instance('user')->save(
			vB::getCurrentSession()->get('userid'), // userid
			'', 									// password
			array(
				// userinfo
				'fbuserid'	=> $fbuserid,
				'fbname'	=> $fbname,
				'fbjoindate' => time(),
			),
			array(),								// options
			array(),								// adminoptions
			array()									// userfield
		);

		if (empty($user) || isset($user['errors']))
		{
			return vB_Library::instance('vb4_functions')->getErrorResponse($user);
		}

		return array('response' => array('errormessage' => array('redirect_updatethanks')));
	}

	/**
	 * Disconnect fb account from the logged in user
	 * 
	 * @param  [int] $confirm 
	 * @return [array]
	 */
	public function fbdisconnect($confirm)
	{
		$userid = vB::getCurrentSession()->get('userid');

		if (!vB_Api::instance('facebook')->isFacebookEnabled())
		{
			return array('response' => array('errormessage' => array('facebook_disabled')));
		}

		if (!empty($userid))
		{
			$data = array(
				'userid' => $userid,
				'password' => '',
				'user' => array(
					'fbuserid' => '',
					'fbname' => '',
					'fbjoindate' => '',
				),
				'options' => array(),
				'adminoptions' => array(),
				'userfield' => array(),
			);
			$user = vB_Api::instance('user')->save($data['userid'], $data['password'], $data['user'], $data['options'], $data['adminoptions'], $data['userfield']);

			if (empty($user) || isset($user['errors']))
			{
				return vB_Library::instance('vb4_functions')->getErrorResponse($user);
			}
			return array('response' => array('errormessage' => array('header_redirect')));
		}

		return array('response' => array('errormessage' => array('nopermission_loggedout')));
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # SVN: $Revision$
|| ####################################################################
\*======================================================================*/
