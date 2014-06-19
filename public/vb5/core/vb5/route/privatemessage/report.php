<?php if(!defined('VB_ENTRY')) die('Access denied.');

/* ======================================================================*\
  || #################################################################### ||
  || # vBulletin 5.0.5 - Licence Number VBFZ48KZZQ
  || # ---------------------------------------------------------------- # ||
  || # Copyright ©2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
  || # This file may not be redistributed in whole or significant part. # ||
  || # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
  || # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
  || #################################################################### ||
  \*====================================================================== */

class vB5_Route_PrivateMessage_Report extends vB5_Route_PrivateMessage_Index
{
	protected $pagenum = 1;
	protected $subtemplate = 'privatemessage_report';

	public function __construct(&$routeInfo, &$matches, &$queryString = '')
	{
		if (isset($matches['params']) AND !empty($matches['params']))
		{
			$paramString = (strpos($matches['params'], '/') === 0) ? substr($matches['params'], 1) : $matches['params'];
			$params = explode('/', $paramString);
			if (!empty($params))
			{
				$this->pageNum = $params[0];
			}

		}
		if (!empty($matches['pagenum']) AND intval($matches['pagenum']))
		{
			$this->pagenum = $matches['pagenum'];
		}

		$routeInfo['arguments']['subtemplate'] = $this->subtemplate;

		parent::__construct($routeInfo, $matches, $queryString, true);
	}

	public function validInput(&$data)
	{
		if (!empty($data['pagenum']))
		{
			$this->pagenum = $data['pagenum'];
		}
		//we don't REQUIRE any parameters.
		return parent::validInput($data);
	}

	public function getUrlParameters()
	{
		return "/{$this->pagenum}";
	}

	public function getParameters()
	{
		return array('pageNum' => $this->pagenum);
	}

	public function getBreadcrumbs()
	{
		$breadcrumbs = array(
				array(
						'phrase' => 'inbox',
						'url'	=> vB5_Route::buildUrl('privatemessage|nosession')
				),
				array(
						'phrase' => 'reports',
						'url' => ''
				)
		);

		return $breadcrumbs;
	}

}

/*======================================================================*\
|| ####################################################################
|| # Downloaded: 12:34, Sat Sep 28th 2013
|| # CVS: $RCSfile$ - $Revision: 40911 $
|| ####################################################################
\*======================================================================*/
