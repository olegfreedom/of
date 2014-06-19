<?php
namespace Base\Mvc;

class ReflectionClass
{
    private $class = null;
    private static $get_type = null;
    
    public function __construct($class) {
        $this->class = $class;
    }
    
    /**
     * Get constant and static property val
     * @param str $name
     * @return $this|str
     */
    public function __get($name) {
        switch ($name) {
            case 'const':
            case 'static':
                self::$get_type = $name;
            break;
            default:
                $class = new \ReflectionClass($this->class);
                
                switch (self::$get_type){
                    case 'const':                      
                        $val = $class->getConstant($name);
                    break;
                    case 'static':
                        $val = $class->getStaticPropertyValue($name);
                    break;
                    default:
                        $val = false;
                    break;
                }

                self::$get_type = null;  
                return $val;
            break;
        }
        
        return $this->class;
    }
}
