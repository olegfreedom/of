<?php
namespace Application\Controller;

class AboutUsController extends \Application\Base\Controller
{
    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('O проекте');
    }
    
    public function indexAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $ret = array(
            'content' => $this->load('Pages', 'admin')->getOne(1)
        );
        
        return $this->view($ret);
    }
}
