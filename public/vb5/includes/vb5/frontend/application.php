<?php
class vB5_Frontend_Application extends vB5_ApplicationAbstract {
	public static function init($configFile)
	{
		parent::init($configFile);

		self::$instance = new vB5_Frontend_Application();
		self::$instance->router = new vB5_Frontend_Routing();
		self::$instance->router->setRoutes();
		self::$instance->router->processExternalLoginProviders();
		$styleid = vB5_Template_Stylevar::instance()->getPreferredStyleId();
		
		if ($styleid)
		{
			vB::getCurrentSession()->set('styleid', $styleid);
		}

		self::ajaxCharsetConvert();
		self::setHeaders();

		return self::$instance;
	}
}
