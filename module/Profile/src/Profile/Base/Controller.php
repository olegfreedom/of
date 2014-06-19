<?php
namespace Profile\Base;

use Zend\Session\Container as SessionContainer;
use Zend\Mvc\MvcEvent;

class Controller extends \Base\Mvc\Controller
{
    protected $session = null;    
    /**
     * Init session
     */
    public function __construct(){
        $this->session = new SessionContainer('profile');
        
        $this->pushTitle('Личный кабинет');

        if(isset($this->session->auth['id'])){
            $this->session->messages = $this->load('Messages', 'profile')->getCount($this->getUserId());
        }

        // add css and js
        $this->addHeadLink('/css/medialoader/application.css', false);
        $this->addHeadScript('/js/tinymce/jquery.tinymce.min.js', false);
        $this->addHeadScript('/js/libs/bootstrap-formhelpers-phone.js', false);
        $this->addHeadScript('/js/medialoader/application.js', false);
    }
    
    /**
     * Check for login
     * @param \Zend\Mvc\MvcEvent $e
     */
    public function onDispatch(MvcEvent $e){
        if($this->session === null){
            return new \Zend\Session\Exception\RuntimeException('Can\'t create session');
        }
        
        if(!isset($this->session->auth['id']) && 
            $this->routeNames('controller') != 'login' && 
            $this->routeNames('controller') != 'registration' && 
            $this->routeNames('controller') != 'forgot' && 
            $this->routeNames('controller') != 'error' &&
            $this->routeNames('controller') != 'guest'

           ){
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array('controller' => 'error'))
                         );
        }

        $this->layout()
            ->setVariables(array(
                'currentUrl' => $this->getCurrentUrl(),
            ));

        return parent::onDispatch($e);
    }

    /**
     * Return current url string without server name
     * @return string
     */
    protected function getCurrentUrl()
    {
        return str_replace('/', '__', $this->getRequest()->getUri()->getPath());
    }
    
    /**
     * Return $this->session->auth['id']
     * @return null|int
     */
    public function getUserId(){
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
     * @param int $id
     */
    protected function setUserId($id = 0){
        if((int)$id > 0){
            $this->setAuth();
            $this->session->auth['id'] = (int)$id;
        }
    }
    
    /**
     * @param string $username
     */
    protected function setUserNаme($username = null){
        if($username !== null){
            $this->setAuth();
            $this->session->auth['username'] = $username;
        }
    }
    
    /**
     * Set auth
     */
    private function setAuth(){
        if(!isset($this->session->auth)){
            $this->session->auth = array();
        }
    }


    public function getAreasAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $region_id = $this->p_int('region_id');
        if ($region_id > 0)
        {
            $ret = array(
                'areasList' => $this->load('AdvertLocation', 'admin')->getAreasByRegion($region_id)
            );
        }

        return $this->json($ret);
    }

    public function getCitiesAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $area_id = $this->p_int('area_id');
        if ($area_id > 0)
        {
            $ret = array(
                'citiesList' => $this->load('AdvertLocation', 'admin')->getCitiesByArea($area_id)
            );
        }

        return $this->json($ret);
    }

    public function getZipAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $city_id = $this->p_int('city_id');
        if ($city_id > 0)
        {
            $ret = array(
                'zipList' => $this->load('AdvertLocation', 'admin')->getZipByCity($city_id)
            );
        }

        return $this->json($ret);
    }

}