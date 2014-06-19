<?php
namespace Api\Controller;

class IndexController extends \Base\Mvc\Controller
{
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        return $this->json(array());
    }
}


