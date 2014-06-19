<?php
namespace Base\Url;

use Zend\EventManager\SharedEventManagerInterface;
use Base\Storage;
use Base\Url\RequestParams;

class RouteNamesLoader
{
    private $sharedEvents;

    /**
     * @param SharedEventManagerInterface $sharedEvents
     */
    public function __construct(SharedEventManagerInterface $sharedEvents) {
        $this->sharedEvents = $sharedEvents;
    }
    
    /**
     * Loading routeNames in Storage 
     * @param __NAMESPACE__ $namespace
     */
    public function load($namespace){
        $this->sharedEvents->attach($namespace, 'dispatch', function($e) {
            $controller = $e->getTarget();
            $route = $controller->getEvent()->getRouteMatch();
            $request = $controller->getRequest();
            
            // get namespace
            $className = $route->getParam('controller', 'Application');
            $classExp = explode('\\', $className);
            $namespace = reset($classExp);
            
            // get names
            $moduleName = strtolower($namespace);
            $controllerName = $route->getParam('__CONTROLLER__', 'index');
            $actionName = $route->getParam('action', 'index');
            
            // get params
            $requestString = $route->getParam('get_http_request_string', '');
            $requestParams = RequestParams::explode($requestString);
            $queryParams = $request->getQuery()->toArray();
            
            // get check action name and class methods
            $actionExp = explode('-', $actionName);
            for($i=1; $i < sizeof($actionExp); $i++){                
                $actionExp[$i] = ucfirst($actionExp[$i]);
            }
            $actionExp[] = 'Action';
            $checkAction = implode('', $actionExp);
            $classMethods = get_class_methods($controller);
            
            // get action found
            $actionFound = in_array($checkAction, $classMethods);
            
            // save to storage
            $storage = new Storage();
            $storage->routeNames = array(                        
                    'controller'=> $controllerName,
                    'action' => $actionName,
                    'module' => $moduleName,
                    'request' => $requestParams,
                    'query' => $queryParams,
                    'actionFound' => $actionFound
            );
        }, 100);
    }
}