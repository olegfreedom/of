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
 * vB_Utilities
 *
 * @package vBApi
 * @access public
 */
class vB_Utilities
{
	public static function vbmkdir($path, $mode = 0777)
	{
		if (is_dir($path))
		{
			if (!(is_writable($path)))
			{
				@chmod($path, $mode);
			}
			return true;
		}
		else
		{
			$oldmask = @umask(0);
			$partialpath = dirname($path);

			if (!self::vbmkdir($partialpath, $mode))
			{
				return false;
			}
			else
			{
				return @mkdir($path, $mode);
			}
		}
	}

	// #############################################################################
	/**
	 * Converts shorthand string version of a size to bytes, 8M = 8388608
	 *
	 * @param	string			The value from ini_get that needs converted to bytes
	 *
	 * @return	integer			Value expanded to bytes
	 */
	public static function ini_size_to_bytes($value)
	{
		$value = trim($value);
		$retval = intval($value);

		switch(strtolower($value[strlen($value) - 1]))
		{
			case 'g':
				$retval *= 1024;
			/* break missing intentionally */
			case 'm':
				$retval *= 1024;
			/* break missing intentionally */
			case 'k':
				$retval *= 1024;
				break;
		}

		return $retval;
	}
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 27657 $
|| ####################################################################
\*======================================================================*/
