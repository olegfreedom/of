<?php
namespace Application\Base;

use Zend\Session\Container as SessionContainer;
use Zend\Mvc\MvcEvent;

class Controller extends \Base\Mvc\Controller
{
    protected $session = null;
    
    /**
     * Init session
     */
    public function __construct(){
        $this->session = new SessionContainer('application');
        
        if(isset($_SESSION['profile']->auth['id'])){
            $_SESSION['profile']->messages = $this->load('Messages', 'profile')->getCount($this->getUserId());
        }
    }
    
    /**
     * Check session
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
    protected function getUserId() {
        return isset($_SESSION['profile']->auth['id']) ? $_SESSION['profile']->auth['id'] : 0;
    }

    /**
     * Return current url string without server name
     * @return string
     */
    protected function getCurrentUrl()
    {
        return str_replace('/', '__', $this->getRequest()->getUri()->getPath());
    }
}