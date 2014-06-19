<?php
namespace Base\Func;

/**
 * Default methods
 */
trait TraitDefault
{    
    /**
     * save log
     * @param str $str __CLASS__.'\\'.__FUNCTION__
     */
    public final function log($str){
        return new \Base\Log();
    }
    
    /**
     * get storage
     * @return \Base\Storage
     */
    public final function storage(){
        return new \Base\Storage();
    }
     
    /**
     * Returns site's base path.
     * 
     * @return string
     */
    public final function basePath(){
        return ($this->storage()->basePath !== null) ? $this->storage()->basePath : '';
    }
         
    /**
     * Returns site email.
     * @return string
     */
    public final function getSiteEmail(){        
        return isset($this->storage()->siteConfig['email']) ? $this->storage()->siteConfig['email'] : 'info@'.$_SERVER['HTTP_HOST'];
    }
    
    /**
     * Returns site name.
     * @return string
     */
    public final function getSiteName(){
        return isset($this->storage()->siteConfig['name']) ? $this->storage()->siteConfig['name'] : 'Site Name';
    } 
    
    /**
     * Load models
     * @param str $model
     * @param str $module
     * @return object
     * @throws \Zend\Mvc\Exception\InvalidArgumentException
     */
    public final function load($model = null, $module = 'application') {
        return \Base\Mvc\ModelsLoader::load($model, $module);
    }
    
    /**
     * Debuger
     * @param mixed $obj
     * @param boolean $isDie
     */
    public final function debug($obj, $isDie = true){        
        \Zend\Debug\Debug::dump($obj);
        
        if($isDie === true){
            die();
        }
    }
    
    /**
     * Json Decode
     * @param json $value
     * @param boolean $toArray
     * @return array|mixed
     */
    public final function jsonDecode($value, $toArray = true){
        if($this->isJson($value) === true){            
            $type = ($toArray === true) ? \Zend\Json\Json::TYPE_ARRAY : \Zend\Json\Json::TYPE_OBJECT;
            return \Zend\Json\Json::decode($value, $type);
        }else{
           return $value; 
        }
    }
    
    /**
     * Json Encode
     * @param array $value
     * @return json|mixed
     */
    public final function jsonEncode($value){
        if(is_array($value)){
            return \Zend\Json\Json::encode($value);
        }else{
            return $value;
        }
    }
    
    
    /**
     * Check json string
     * @param json $json
     * @return boolean
     */
    public final function isJson($json){
        $ret = false;
        
        @json_decode($json);
        if(json_last_error() === JSON_ERROR_NONE){
            $ret = true;
        }
        
        return $ret;
    } 
}
