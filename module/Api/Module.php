<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Api;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Base\Storage;
use Base\Url\RouteNamesLoader;

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
    
    public function getControllerConfig()
    {
        return array(
            'abstract_factories' => array(
                'Base\Factory\AutoloadControllersFactory'
            ),
        );
    }
}
