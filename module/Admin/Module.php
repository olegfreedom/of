<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Admin;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\ModuleManager;
use Base\Mvc\MediaLoader;
use Base\Url\EasyUrl;
use Base\Storage;
use Base\Url\RouteNamesLoader;
use Base\Url\RouteNamesHelper;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {        
        $e->getApplication()->getServiceManager()->get('translator');
        $eventManager = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        $config = $e->getApplication()->getServiceManager()->get('Config');
        $storage = new Storage();
        $storage->dbConfig = $config['db'];
        $storage->siteConfig = $config['site'];
        $storage->basePath = $config['view_manager']['base_path'];

        $sharedEvents = $eventManager->getSharedManager();
        $routeNames = new RouteNamesLoader($sharedEvents);
        $routeNames->load(__NAMESPACE__);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    
    public function init(ModuleManager $moduleManager) {
        $sharedEvents = $moduleManager->getEventManager()->getSharedManager();
        $sharedEvents->attach(__NAMESPACE__, 'dispatch', function($e) {
            $controller = $e->getTarget();
            $controller->layout('admin/layout');
        }, 100);
    }
    
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'mediaLoader' => function() {
                    return new MediaLoader();
                },
                'easyUrl' => function() {
                    return new EasyUrl();
                },
                'routeNames' => function() {                   
                    return new RouteNamesHelper();
                },
            ),
        );
    }
        
    public function getControllerConfig()
    {
        return array(
            'abstract_factories' => array(
                'Base\Factory\AutoloadControllersFactory'
            ),
        );
    }
}
