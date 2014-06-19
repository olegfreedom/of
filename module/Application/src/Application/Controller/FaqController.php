<?php
namespace Application\Controller;

class FaqController extends \Application\Base\Controller
{
    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('FAQ');
        $this->addHeadScript('/js/libs/jquery.openclose.js',false);
    }
    
    public function indexAction() {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array(
            'faqList' => $this->load('Faq', 'admin')->getList()
        );
        
        $anchor = $this->p_string('howto','no');
        if (isset($anchor) && $anchor != 'no') {
            $this->addHeadScript('/js/medialoader/application/faq/anchor-scroll.js',false);
        }
        
        return $this->view($ret);
    }
}
