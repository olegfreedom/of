<?php
namespace Admin\Base;

use Zend\Session\Container as SessionContainer;
use Zend\Mvc\MvcEvent;

class Controller extends \Base\Mvc\Controller
{
    protected $session = null;
    
    /**
     * Init session
     */
    public function __construct(){
        $this->session = new SessionContainer('admin');
    }
    
    /**
     * Check for login
     * @param \Zend\Mvc\MvcEvent $e
     */
    public function onDispatch(MvcEvent $e){
        if($this->session === null){
            return new \Zend\Session\Exception\RuntimeException('Can\'t create session');
        }
        
        if(!isset($this->session->auth['id']) && $this->routeNames('controller') != 'login'){
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array('controller' => 'login'))
                         );
        }

        return parent::onDispatch($e);
    }
    
    protected function getUserId(){
        return isset($this->session->auth['id']) ? $this->session->auth['id'] : null;
    }
    
    protected function getUserNаme(){
        return isset($this->session->auth['username']) ? $this->session->auth['username'] : null;
    }
}