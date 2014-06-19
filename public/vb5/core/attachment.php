<?php
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
if (!defined('VB_ENTRY'))
{
	define('VB_ENTRY', 1);
}

if (!empty($_REQUEST['attachmentid']))
{
	$oldid = intval($_REQUEST['attachmentid']);
	$db = vB::getDbAssertor();
	$node = $db->getRow('vBForum:node', array('oldid'=>$oldid, 'oldcontenttypeid'=>array(
		vB_Api_ContentType::OLDTYPE_SGPHOTO,
		vB_Api_ContentType::OLDTYPE_PHOTO,
		vB_Api_ContentType::OLDTYPE_THREADATTACHMENT,
		vB_Api_ContentType::OLDTYPE_POSTATTACHMENT,
		vB_Api_ContentType::OLDTYPE_BLOGATTACHMENT)));
	if ($node)
	{
		switch($node['oldcontenttypeid'])
		{
		case vB_Api_ContentType::OLDTYPE_SGPHOTO:
		case vB_Api_ContentType::OLDTYPE_PHOTO:
			$requestvar = 'photoid';
			break;
		default:
			$requestvar = 'id';
		}
		$redirecturl = vB::getDatastore()->getOption('frontendurl') . "/filedata/fetch?${requestvar}=$node[nodeid]";
		header("Location: $redirecturl", true, 301);
		exit;
	}
}

throw new vB5_Exception_404("invalid_page_url");

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 75544 $
|| ####################################################################
\*======================================================================*/
?>
