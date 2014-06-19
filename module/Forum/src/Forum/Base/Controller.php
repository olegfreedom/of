<?php
namespace Forum\Base;

use Zend\Session\Container as SessionContainer;
use Zend\Mvc\MvcEvent;

class Controller extends \Base\Mvc\Controller
{
    protected $session = null;    
    /**
     * Init session
     */
    public function __construct(){
        $this->session = new SessionContainer('forum');
        
        $this->pushTitle('Личный кабинет');
         
        $arObj['auth']['id'] = 1;
        $this->session = (object)$arObj;
//        if(isset($this->session->auth['id'])){
//            $this->session->messages = $this->load('Messages', 'profile')->getCount($this->getUserId());
//        }
        
    }
    
    /**
     * Check for login
     * @param \Zend\Mvc\MvcEvent $e
     */
    public function onDispatch(MvcEvent $e){
        if($this->session === null){
            return new \Zend\Session\Exception\RuntimeException('Can\'t create session');
        }

        $this->layout()
            ->setVariables(array(
                'currentUrl' => $this->getCurrentUrl(),
            ));

        return parent::onDispatch($e);
    }
    
    /**
     * Return $this->session->auth['id']
     * @return null|int
     */
    protected function getUserId(){
        return isset($this->session->auth['id']) ? $this->session->auth['id'] : 0;
    }
    
    /**
     * Return $this->session->auth['username']
     * @return null|string
     */
    protected function getUserNаme(){
        return isset($this->session->auth['username']) ? $this->session->auth['username'] : null;
    }

    /**
     * Return URL
     * @return null|string
     */
    protected function getCurrentUrl()
    {
        return str_replace('/', '__', $this->getRequest()->getUri()->getPath());
    }
}