<?php
namespace Base\Mvc;

class ModelsLoader
{
    private static $models = array();
    private static $controllerInstance = null;

    /**
     * Load models
     * @param str $model
     * @param str $module
     * @return object
     * @throws \Zend\Mvc\Exception\InvalidArgumentException
     */
    public static function load($model, $module, $ControllerInstance = null) {
        if($model !== null){
            $module = ucfirst($module);
            $path = '\\'.$module.'\\Model\\'.$model;

            if(!isset(self::$models[$path])){                
                if(class_exists($path)){
                    self::$models[$path] = new $path();
                }else{
                    throw new \Zend\Mvc\Exception\InvalidArgumentException('Not found model '.$path);
                }
            }

            // Every call update controller instance
            if ( !is_null($ControllerInstance) )
            {
                self::$controllerInstance = $ControllerInstance;
            }

            return self::$models[$path];
        }
        
        throw new \Zend\Mvc\Exception\InvalidArgumentException('Model name is null');
    }

    public static function getController()
    {
        return self::$controllerInstance;
    }
}
