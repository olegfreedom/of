<?php if (!defined('VB_ENTRY')) die('Access denied.');
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/

/**
 * This is the MySQL-Specific version of the select query processor
 *
 * @package vBDatabase
 * @version $Revision: 28823 $
 */
class vB_dB_Query_Select_MYSQL extends vB_dB_Query_Select
{
	/*Properties====================================================================*/

	protected $db_type = 'MYSQL';
}

/*======================================================================*\
|| ####################################################################
|| # Downloaded=> 03:20, Thu Sep 26th 2013
|| # SVN=> $Revision=> 28823 $
|| ####################################################################
\*======================================================================*/