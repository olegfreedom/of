<?php
namespace Application\Controller;

class UserAgreementController extends \Application\Base\Controller
{
    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('Пользовательское соглашение');
    }
    
    public function indexAction() {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array(
            'content' => $this->load('Pages', 'admin')->getOne(3),
        );

        return $this->view($ret);
    }

}
