<?php
namespace Profile\Controller;

class ErrorController extends \Profile\Base\Controller
{  
    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('Ошибка');
    }
    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function loginAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function registrationAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function forgotAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function starsAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array();
        
        return $this->view($ret);
    }
}
