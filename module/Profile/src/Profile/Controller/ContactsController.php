<?php
namespace Profile\Controller;

class ContactsController extends \Profile\Base\Controller
{
    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('Контактная информация');
    }
    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__); 
        
        $ret = array(
            'getEdit' => $this->load('Contacts', 'profile')->get($this->getUserId()),
            'getPhone' => $this->load('UsersPhone', 'admin')->get($this->getUserId()),
            'phoneMask' => $this->load('Phone', 'admin')->getPhoneMask(),
            'phonePlaceholder' => $this->load('Phone', 'admin')->getPlaceholder(),
            'phoneMaskArray' => $this->load('Phone', 'admin')->getMaskArray(),
        );        

        return $this->view($ret);
    }
    
    public function editAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__); 
        
        if($this->p_int('contacts-form') === 1){
            $params = array(
                'name' => $this->p_string('person'),
            );
            
            $arrays = array(
                'phone' => $this->p_array('phone'),
                'mask' => $this->p_array('mask'),
            );
            
            $check_params = array(
                'name' => $params['name'],
                'phone' => $arrays['phone'],
            );
            $check = $this->check($check_params);
            if($check['status'] == true){
                $res = $this->load('Contacts', 'profile')->edit($params, $arrays, $this->getUserId());
                if($res == true){                    
                    return $this->redirect()
                                 ->toUrl(
                                     $this->easyUrl(array('action' => 'success'))
                                 );
                }
            }
        }
        
        return $this->redirect()
                     ->toUrl(
                         $this->easyUrl(array('action' => 'error'))
                     );
    }
    
    public function successAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__); 
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function errorAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__); 
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'name' => $this->p_string('name'),
            'phone' => $this->p_array('phone')
        );
        
        $ret = $this->check($params);
        
        return $this->json($ret);
    }
    
    
    /**
     * @param array $params
     * @return array
     */
    private function check($params = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $error = array();
                      
        $validItem = $this->load('Validator')->validStringLength($params['name'], 5, 100);
        if($validItem == false){
            $error['name'] = $validItem;
        }
        
        foreach($params['phone'] as $key => $val){
            if(!empty($val)){                
                $validItem = $this->load('Phone', 'admin')->checkPhone($val);
                if($validItem == false){
                    $error['phone'][$key] = $validItem;
                }
            }
        }
        
        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $ret;
    }
    
 /*********************************************************************************
                                Phone actions
 ********************************************************************************/
    
    public function removePhoneAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $id = $this->p_int('id');
        
        $ret = array(
            'status' => $this->load('UsersPhone', 'admin')->remove($id, $this->getUserId())
        );
        
        return $this->json($ret);
    }
}
