<?php
namespace Application\Controller;

class ContactUsController extends \Application\Base\Controller
{
    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('Обратная связь');
    }
    
    public function indexAction() {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array(
            'content' => $this->load('Pages', 'admin')->getOne(2),
        );

        return $this->view($ret);
    }
    
    public function addAction() {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'name' => $this->p_string('name'),
            'email' => $this->p_string('email'),
            'message' => $this->p_string('message')
        );

        $ret['status'] = $this->load('ContactUs')->add($params);

        return $this->json($ret);
    }
    
   
    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'name' => $this->p_string('name'),
            'email' => $this->p_string('email'),
            'message' => $this->p_string('message')
        );
        
        $error = array();
        
        $validItem = $this->load('Validator')->validStringLength($params['name'], 2, 150);
        if($validItem == false){
            $error['name'] = $validItem;
        }
        
        $validItem = $this->load('Validator')->validStringLength($params['email'], 5, 200);
        if($validItem == false){
            $error['email'] = $validItem;
        }else{
            $validItem = $this->load('Validator')->validEmail($params['email']);
            if($validItem == false){
                $error['email'] = $validItem;
            }
        }
        
        $validItem = $this->load('Validator')->validStringLength($params['message'], 5, 1000);
        if($validItem == false){
            $error['message'] = $validItem;
        }
        
        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $this->json($ret);
    }
   
}
