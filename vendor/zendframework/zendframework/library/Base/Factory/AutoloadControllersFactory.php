<?php
namespace Base\Factory;
 
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
 
class AutoloadControllersFactory implements AbstractFactoryInterface
{
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (class_exists($requestedName.'Controller')){
            return true;
        }else{
            return false;
        }
    }
 
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $class = $requestedName.'Controller';
        return new $class;
    }
}