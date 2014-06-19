<?php
namespace Forum\Controller;

class IndexController extends \Forum\Base\AbstractAdvertsController
{
    public function __construct()
    {
        parent::__construct();
        $this->pushTitle('Управление группами');

    }

    public function indexAction(){

        return $this->view();
    }
    public function createGroupAction(){
        
        return $this->view();
    }
    public function editGroupAction(){
        
        return $this->view();
    }
    public function deleteGroupAction(){
        
        return $this->view();
    }
}
