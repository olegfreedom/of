<?php
namespace Admin\Controller;

class IndexController extends \Admin\Base\Controller
{    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $ret = array();
        
        return $this->view($ret);
    }
}
