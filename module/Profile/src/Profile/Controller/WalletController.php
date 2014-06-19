<?php
namespace Profile\Controller;

class WalletController extends \Profile\Base\Controller
{  
    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('Кошелек');
    }
    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array(
            'getStar' => $this->session->star,
            'getPriceRate' => $this->load('Wallet', 'profile')->const->PRICE_RATE
        );
        
        return $this->view($ret);
    }
}
