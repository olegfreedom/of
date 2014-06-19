<?php
namespace Base\Url;

use Zend\View\Helper\AbstractHelper;
use Base\Storage;

class RouteNamesHelper extends AbstractHelper
{    
    /**
     * 
     * @param str $name
     * @return str|null
     */
    public function __invoke($name) {
        $storage = new Storage();
        
        return isset($storage->routeNames[$name]) ? $storage->routeNames[$name] : null;
    }
}