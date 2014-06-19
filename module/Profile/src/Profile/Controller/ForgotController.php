<?php
namespace Profile\Controller;

class ForgotController extends \Profile\Base\Controller
{
    const USER_NOT_EXISTS_ERROR_MESSAGE = 'Извините, такой Email в системе не зарегистрирован. Пожалуйста, проверьте правильность написания Email.';

    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__); 
        
        if(isset($this->session->auth['id'])){
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array())
                         );
        }
        $ret = array();
        
        if($this->p_int('forgot-form') === 1){
            $params = array(
                'username' => $this->p_string('username'),
                'link' => $this->easyUrl(array('action' => 'confirm-recovery', 'key'=> '__KEY__'))
            );

            $check = $this->check($params);
            if($check['status'] == true){
                $userKey = $this->load('Forgot', 'profile')->queryRecovery($params);

                if($userKey){
                    return $this->redirect()
                                 ->toUrl(
                                     $this->easyUrl(array('action' => 'recover'))
                                 );
                }
            }
        }
        return $this->view($ret);
    }

    public function confirmRecoveryAction() {
        $key = $this->p_string('key');

        $username = $this->load('Forgot', 'profile')->getUserByKey($key);
        $username_temp = $this->load('Forgot', 'profile')->getUserByKeyTemp($key);

        if($username['username'] == $username_temp['username']){
            $user = true;
            $this->load('Forgot', 'profile')->clearKeyTemp(0);
        }
        if ( $user )
        {
            $params = array(
                'username' => $username ['username'],
            );

            $this->load('Forgot', 'profile')->recover($params);

            return $this->redirect()->toUrl($this->easyUrl(array('action' => 'success')));
        }
        else
        {
            return $this->redirect()->toUrl($this->easyUrl(array('action' => 'error')));
        }
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
    
    public function recoverAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__); 
        
        $ret = array();
        
        return $this->view($ret);
    }
    
    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'username' => $this->p_string('username')
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

        $errorAr = array();
        $messagesAr = array();
        $Validator = $this->load('Validator');

        $field = 'username';
        if ( !$Validator->validEmail($params[$field]) )
        {
            $errorAr [$field] = FALSE;
            $messagesAr [$field] = $Validator->getErrors();
        }
        elseif ( !$this->load('User', 'profile')->checkLogin($params[$field]) )
        {
            $errorAr [$field] = FALSE;
            $messagesAr [$field] = array(self::USER_NOT_EXISTS_ERROR_MESSAGE);
        }

        $ret = array(
            'status' => empty($errorAr),
            'error' => $errorAr,
            'messages' => $messagesAr,
        );
        
        return $ret;
    }
}
