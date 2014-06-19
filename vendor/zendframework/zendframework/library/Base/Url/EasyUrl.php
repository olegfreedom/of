<?php
namespace Base\Url;

use Zend\View\Helper\AbstractHelper;
use Base\Storage;

class EasyUrl extends AbstractHelper 
{    
    /**
     * 
     * @param array $params
     * @param array $query
     * @param bool $reuseMatchedParams Whether to reuse matched parameters
     * @return str
     */
    public function __invoke($params = array(), $query = array(), $reuseMatchedParams = false){
        $view = $this->getView();
        $storage = new Storage();
        
        $data = self::url((array)$params, (array)$query, (array)$storage->routeNames, (bool)$reuseMatchedParams);
        
        return $view->basePath().self::decode(
                $view->url(
                    $data['defaultRouter'], 
                    $data['urlParams'], 
                    $data['urlQuery']
               )
        );
    }
    
    /**
     * 
     * @param array $params
     * @param array $query
     * @param array $routeNames
     * @return array
     */
    public static function url($params = array(), $query = array(), $routeNames = array(), $reuseMatchedParams = false){
        $urlParams = array();
        $urlQuery = array();
        
        if($reuseMatchedParams == true){
            foreach($params as $key=>$val){
                if(isset($routeNames['request'][$key])){
                    unset($routeNames['request'][$key]);
                }
            }
            $params = array_merge($params, $routeNames['request']);
            
            foreach($query as $key=>$val){
                if(isset($routeNames['query'][$key])){
                    unset($routeNames['query'][$key]);
                }
            }
            $query = array_merge($query, $routeNames['query']);
        }
        
        if(sizeof($params) == 0){
            $defaultRouter = $routeNames['module'];
        }else{            
            // set module
            if(isset($params['module']) && $params['module']){
                $defaultRouter = $params['module'].'/default';
                unset($params['module']);
            }else{
                $defaultRouter = $routeNames['module'].'/default';
            }

            // set controller
            if(isset($params['controller']) && $params['controller']){
                $urlParams['controller'] = $params['controller'];
                unset($params['controller']);
            }else{
                if(sizeof($params) > 0){
                    $urlParams['controller'] = $routeNames['controller'];
                }
            }

            // set action
            if(isset($params['action']) && $params['action']){
                $urlParams['action'] = $params['action'];
                unset($params['action']);
            }else{
                if(sizeof($params) > 0){
                    $urlParams['action'] = $routeNames['action'];
                }
            }

            // set request
            if(sizeof($params) > 0){
                $request = array();

                foreach($params as $key=>$val){
                    if($val !== null){                        
                        $request[] = urlencode($key);
                        $request[] = urlencode($val);
                    }
                }

                if(sizeof($request) > 0){
                    $urlParams['get_http_request_string'] = implode('/', $request);
                }
            }
        }
        
        // set query
        if(sizeof($query) > 0){
            $urlQuery = array(
                'query' => $query
            );
        }
        
        return array(
            'defaultRouter' => $defaultRouter, 
            'urlParams' => $urlParams,
            'urlQuery' => $urlQuery,
        );
    }
    
    
    /**
     * 
     * @param str $url
     * @return str
     */
    public static function decode($url){
        $exp = explode('?', $url);
        $exp[0] = urldecode($exp[0]);
        
        return implode('?', $exp);
    }
}