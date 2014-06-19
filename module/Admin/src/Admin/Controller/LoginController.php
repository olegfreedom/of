<?php
namespace Admin\Controller;

class LoginController extends \Admin\Base\Controller
{
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $this->unsetAuth();
        
        
        if($this->p_int('login-form') === 1){
            $params = array(
                'username' => $this->p_string('username'),
                'password' => $this->p_string('password')
            );
            
            $user = $this->load('Login', 'admin')->authUser($params);
        
            return $this->setAuth($user);
        }
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function logoutAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $this->unsetAuth();
            
        return $this->redirect()
                     ->toUrl(
                         $this->easyUrl(array('controller' => 'login'))
                     );
    }
    
    /**
     * Unset login
     */
    private function unsetAuth(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if(isset($this->session->auth['id'])){
            unset($this->session->auth);
        }
    }
    
    /**
     * Set login
     */
    private function setAuth($user = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if(isset($user['userid']) && isset($user['username'])){
            $this->session->auth = array(
                'id' => (int)$user['userid'],
                'username' => (string)$user['username']
            );
            
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array())
                         );
        }else{
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array('controller' => 'login'))
                         );
        }
    }
}
