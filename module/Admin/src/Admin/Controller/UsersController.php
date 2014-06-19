<?php
namespace Admin\Controller;

class UsersController extends \Admin\Base\Controller
{    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $page = $this->p_int('page', 1);
        $ret = array(
            'usersList' => $this->load('Users', 'admin')->getList($page),
            'paginator' => $this->load('Users', 'admin')->getPaginator($page)
        );

        return $this->view($ret);
    }
    
    public function editAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if($this->p_int('edit-form') === 1){
            $params = array(
                'username' => $this->p_string('username'),
                'email' => $this->p_string('email'),
                'password' => $this->p_string('password'),
                'retry_password' => $this->p_string('retry_password'),
                'status' => $this->p_select('status', 'n', array('y', 'n'))
            );
            
            $arrays = array(
                'phone' => $this->p_array('phone'),
                'mask' => $this->p_array('mask'),
            );
            
            $this->load('Users', 'admin')->edit($this->p_int('id'), $params, $arrays);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );                 
        }else{      
            $id = $this->p_int('userid');
            
            $ret = array(
                'getEdit' => $this->load('Users', 'admin')->getOne($id),
                'getPhone' => $this->load('UsersPhone', 'admin')->get($id),
                'phoneMask' => $this->load('Phone', 'admin')->getPhoneMask(),
                'phonePlaceholder' => $this->load('Phone', 'admin')->getPlaceholder(),
                'phoneMaskArray' => $this->load('Phone', 'admin')->getMaskArray(),
            );

            return $this->view($ret);
        }

    }
    
    public function removeAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->load('Users', 'admin')->remove($this->p_int('id'));
        
        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index'))
        );
    }
    
    public function statusAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->load('Users', 'admin')->setStatus($this->p_int('userid'));
        
        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index', 'userid' => null), array(), true)
        );
    }
    
    public function removePhoneAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $id = $this->p_int('id');
        $status = $this->load('UsersPhone', 'admin')->remove($id);
        
        return $this->json(array('status' => $status));
    }
    
    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'username' => $this->p_string('username'),
            'email' => $this->p_string('email'),
            'password' => $this->p_string('password'),
            'retry_password' => $this->p_string('retry_password'),
            'phoneArray' => $this->p_array('phoneArray'),
        );

        $error = array();

        $validItem = $this->load('Validator')->validStringLength($params['username'], 0, 100);
        if($validItem == false){
            $error['username'] = $validItem;
        }

        /*$validItem = $this->load('Validator')->validStringLength($params['email'], 5, 100);
        if($validItem == false){
            $error['email'] = $validItem;
        }else{
            $validItem = $this->load('Validator')->validEmail($params['email']);
            if($validItem == false){
                $error['email'] = $validItem;
            }else{
                $validItem = $this->load('User', 'profile')->checkLogin($params['email'], $this->p_int('user'));
                if($validItem == true){
                    $error['email'] = $validItem;
                }
            }
        }*/
        
        if(!empty($params['password'])){
            $validItem = $this->load('Validator')->validIdentical($params['password'], $params['retry_password']);
            if($validItem == false){
                $error['retry_password'] = $validItem;
            }else{                
                $validItem = $this->load('Validator')->validStringLength($params['password'], 5, 100);
                if($validItem == false){
                    $error['password'] = $validItem;
                }
            }
        }
        
        foreach($params['phoneArray'] as $key => $val){
            if(!empty($val)){                
                $validItem = $this->load('Phone', 'admin')->checkPhone($val);
                if($validItem == false){
                    $error['phoneArray'][$key] = $validItem;
                }
            }
        }       
        
        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $this->json($ret);
    }
}
