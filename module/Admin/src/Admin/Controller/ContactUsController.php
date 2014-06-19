<?php
namespace Admin\Controller;

class ContactUsController extends \Admin\Base\Controller {
    
    public function indexAction() {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $params = array(
            'page' => $this->p_int('page', 1)
        );
        
        $ret = array(
            'contactUsList' => $this->load('ContactUs', 'admin')->getList($params),
            'paginator' => $this->load('ContactUs', 'admin')->getPaginator($params)
        );

        return $this->view($ret);
    }
    
    public function editAction() {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        if($this->p_int('edit-form') === 1) {
            
            $params = array(
                'name' => $this->p_string('name'),
                'email' => $this->p_string('email'),
                'message' => $this->p_string('message')
            );
            
            $this->load('ContactUs', 'admin')->edit($this->p_int('id'), $params);

            return $this->redirect()->toUrl(
                        $this->easyUrl(array('action'=>'index'))
                    );
        
        } else {
            $ret = array(
                'getEdit' => $this->load('ContactUs', 'admin')->getOne($this->p_int('id'))
            );
            
            return $this->view($ret);
        }
    }
    
    public function removeAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->load('ContactUs', 'admin')->remove($this->p_int('id'));
        
        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index'))
        );
    }
    
    public function validatorAction() {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
                'name' => $this->p_string('name'),
                'email' => $this->p_string('email'),
                'message' => $this->p_string('message','',false)
        );
        
        $error = array();

        $validItem = $this->load('Validator')->validStringLength($params['name'], 3, 100);
        if($validItem == false){
            $error['name'] = $validItem;
        }

        $validItem = $this->load('Validator')->validStringLength($params['email'], 5, 200);
        if($validItem == false){
            $error['email'] = $validItem;
        } else {
            $validItem = $this->load('Validator')->validEmail($params['email']);
            if($validItem == false){
                $error['email'] = $validItem;
            }
        }
        
        $validItem = $this->load('Validator')->validStringLength($params['message'], 5, 2000);
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