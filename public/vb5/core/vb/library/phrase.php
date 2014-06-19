<?php
if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
|| # ---------------------------------------------------------------- # ||
|| # Copyright 2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/


/**
 * vB_Library_Phrase
 *
 * @package vBApi
 * @access public
 */

class vB_Library_Phrase extends vB_Library
{

	const VALID_CLASS = 'A-Za-z0-9_\.\[\]';

	/**
	 * Add a new phrase or update an existing phrase
	 * @param string $fieldname New Phrase Type for adding, old Phrase Type for editing
	 * @param string $varname New Varname for adding, old Varname for editing
	 * @param array $data Phrase data to be added or updated
	 *              'text' => Phrase text array.
	 *              'oldvarname' => Old varname for editing only
	 *              'oldfieldname' => Old fieldname for editing only
	 *              't' =>
	 *              'ismaster' =>
	 *              'product' => Product ID of the phrase
	 * @return void
	 */
	public function save($fieldname, $varname, $data)
	{
		$fieldname = trim($fieldname);
		$varname = trim($varname);
		$vb5_config =& vB::getConfig();
		$install = false;
		if (defined('VBINSTALL') AND VBINSTALL)
		{
			$install = true;
		}
		$session = vB::getCurrentSession();
		if (!empty($session))
		{
			$userinfo = $session->fetch_userinfo();
		}
		else
		{
			$userinfo = vB_User::fetchUserinfo(1);
		}
		require_once(DIR . '/includes/adminfunctions.php');
		$full_product_info = fetch_product_list(true);

		if (empty($varname))
		{
			throw new vB_Exception_Api('please_complete_required_fields');
		}

		if (!preg_match('#^[' . self::VALID_CLASS . ']+$#', $varname)) // match a-z, A-Z, 0-9, '.', ',', _ only .. allow [] for help items
		{
			throw new vB_Exception_Api('invalid_phrase_varname');
		}

		require_once(DIR . '/includes/functions_misc.php');
		foreach ($data['text'] AS $text)
		{
			if (!validate_string_for_interpolation($text))
			{
				throw new vB_Exception_Api('phrase_text_not_safe', array($varname));
			}
		}

		// it's an update
		if (!empty($data['oldvarname']) AND !empty($data['oldfieldname']))
		{
			if (
				vB::getDbAssertor()->getField('phrase_fetchid', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_STORED,
					'varname' => $varname,
				))
			)
			{
				// Don't check if we are moving a phrase to another group but keeping the same name. See VBV-4192.
				if ($varname != $data['oldvarname'] AND $fieldname != $data['oldfieldname'])
				{
					throw new vB_Exception_Api('there_is_already_phrase_named_x', array($varname));
				}

				if ($varname != $data['oldvarname'])
				{
					throw new vB_Exception_Api('variable_name_exists', array($data['oldvarname'], $varname));
				}

			}

			if (!is_array($data['oldfieldname']))
			{
				$data['oldfieldname'] = array($data['oldfieldname']);
			}

			if (!in_array($fieldname, $data['oldfieldname']))
			{
				$data['oldfieldname'][] = $fieldname;
			}
			// delete old phrases
			vB::getDbAssertor()->assertQuery('deleteOldPhrases', array(
				'varname' => $data['oldvarname'],
				'fieldname' => $data['oldfieldname'],
				't' => $data['t'],
				'debug' => (empty($data['skipdebug']) && ($vb5_config['Misc']['debug'] OR $install)),
			));

			$update = 1;
			$this->setPhraseDate();
		}

		if (empty($update))
		{
			if ((empty($data['text'][0]) AND $data['text'][0] != '0' AND !$data['t']) OR empty($varname))
			{
				throw new vB_Exception_Api('please_complete_required_fields');
			}

			if (
				vB::getDbAssertor()->getField('phrase_fetchid', array(
					'varname' => $varname,
					'fieldname' => $fieldname,
				))
			)
			{
				throw new vB_Exception_Api('there_is_already_phrase_named_x', array($varname));
			}
		}

		if ($data['ismaster'])
		{
			if (($vb5_config['Misc']['debug'] OR $install) AND !$data['t'])
			{
				/*insert query*/
				vB::getDbAssertor()->assertQuery('phrase', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_REPLACE,
					'languageid' => -1,
					'varname' => $varname,
					'text' => $data['text'][0],
					'fieldname' => $fieldname,
					'product' => $data['product'],
					'username' => $userinfo['username'],
					'dateline' => vB::getRequest()->getTimeNow(),
					'version' =>$full_product_info[$data['product']]['version']
				));
			}

			unset($data['text'][0]);
		}

		foreach($data['text'] AS $_languageid => $txt)
		{
			$_languageid = intval($_languageid);

			if (!empty($txt) OR $txt == '0')
			{
				/*insert query*/
				vB::getDbAssertor()->assertQuery('phrase', array(
					vB_dB_Query::TYPE_KEY => vB_dB_Query::QUERY_REPLACE,
					'languageid' => $_languageid,
					'varname' => $varname,
					'text' => $txt,
					'fieldname' => $fieldname,
					'product' => $data['product'],
					'username' => $userinfo['username'],
					'dateline' => vB::getRequest()->getTimeNow(),
					'version' =>$full_product_info[$data['product']]['version']
				));
			}
		}

		require_once(DIR . '/includes/adminfunctions.php');
		require_once(DIR . '/includes/adminfunctions_language.php');
		build_language(-1);
	}

	/**
	 * Resets the phrase cachebuster date.
	 */
	public function setPhraseDate()
	{
		vB_Cache::instance()->event("vB_Language_languageCache");
		$options = vB::getDatastore()->getValue('miscoptions');
		$options['phrasedate'] = vB::getRequest()->getTimeNow();
		vB::getDatastore()->build('miscoptions', serialize($options), 1);
	}

	/**
	 * Cleans a guid to match phrase valid class (self::VALID_CLASS).
	 * This is used to build phrases for import items.
	 * Example: title and description for pages
	 *				- 'page_' . $guidforphrase . '_title'
	 *				- 'page_' . $guidforphrase . '_description'
	 *
	 * @param 	string 	GUID string.
	 *
	 * @return 	string 	GUID for phrase.
	 **/
	public function cleanGuidForPhrase($guid)
	{
		$guidforphrase = str_replace(array('.', 'vbulletin-'), array(''), $guid);
		$guidforphrase = str_replace(array('-'), array('_'), $guidforphrase);

		return $guidforphrase;
	}

}
