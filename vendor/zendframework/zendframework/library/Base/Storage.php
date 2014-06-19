<?php
namespace Base;

class Storage
{
    private static $params = array();

    /**
     * 
     * @param str $name
     * @param str $value
     */
    public function __set($name, $value) {
        self::$params[$name] = $value;
    }
    
    /**
     * 
     * @param str $name
     * @return str
     */
    public function __get($name) {
        return isset(self::$params[$name]) ? self::$params[$name] : null;
    }
}