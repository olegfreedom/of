<?php
namespace Base\Text;

use Zend\View\Helper\AbstractHelper;
use Base\Storage;

class SiteNameHelper extends AbstractHelper
{    
    /**
     * 
     * @param str $name
     * @return str|null
     */
    public function __invoke() {
        $storage = new Storage();
        
        return isset($storage->siteConfig['name']) ? $storage->siteConfig['name'] : 'Site Name';
    }
}