<?php
namespace Profile\Controller;

use Profile\Model\CaptchaForm;
use Zend\Session\Config\StandardConfig;
use Zend\Session\SessionManager;
use Zend\Session\Container;

class LoginController extends \Profile\Base\Controller
{    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $this->unsetAuth();

        $referrer = $this->p_string('referrer');

        $ret = array(
            'captcha' => new CaptchaForm(),
            'referrer' => str_replace('__', '/', $referrer),
            'error' => '',
        );
        
        if($this->p_int('login-form') === 1){
            $params = array(
                'username' => $this->p_string('username'),
                'password' => $this->p_string('password'),
            );

            $user = null;
            $check = $this->check($params);

            if(count($check) > 0){
                $user = $this->load('Login', 'profile')->authUser($params);

                if($user === null){
                    $ret ['error'] = 'Неверное имя пользователя или пароль.';
                    return $this->view($ret);
                }
            }

            $saveme = $this->p_int('saveme');

            return $this->setAuth($user, $referrer, $saveme);
        }

        $req = $this->getRequest();
        if ( $req->isPost() ){
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array('controller' => 'error'))
                         );
        }

        return $this->view($ret);
    }
    
    public function logoutAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->load('Vbulletin', 'profile')->logout();

        $this->unsetAuth();
            
        return $this->redirect()->toUrl('/');
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
    private function setAuth($user = null, $referrer = '', $saveme = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ( isset($user ['userid']) && isset($user ['username']) )
        {
            if ( !empty($saveme) )
            {
                $config = new StandardConfig();
                $config->setOptions(array(
                    'remember_me_seconds' => 24*60*60,
                    'name'                => 'profile',
                ));
                $sessionManager = new SessionManager($config);
                Container::setDefaultManager($sessionManager);
            }

            $this->session->auth = array(
                'id' => (int) $user ['userid'],
                'username' => (string) $user ['username']
            );

            if ( !empty($referrer) )
            {
                return $this->redirect()
                    ->toUrl($referrer);
            }
            else
            {
                return $this->redirect()
                             ->toUrl(
                                 $this->easyUrl(array('module' => 'application', 'controller' => 'index'))
                             );
            }
        }else{
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array('controller' => 'error', 'action' => 'login'))
                         );
        }
    }
    
    public function loginByKeyAction() {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $key = $this->p_string('key');

        if (!empty($key)) {
            $user = $this->load('Forgot', 'profile')->getUserByKey($key);
        }
        
        if(isset($user['id']) && isset($user['username'])){ 
            return $this->setAuth($user);
        }
    }
    
    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'username' => $this->p_string('username'),
            'password' => $this->p_string('password'),
            'captcha' => $this->p_array('captcha')
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
        
        $validItem = $this->load('Validator')->validStringLength($params['username'], 5, 100);
        if($validItem == false){
            $error['username'] = $validItem;
        }else{
            $validItem = $this->load('Validator')->validEmail($params['username']);
            if($validItem == false){
                $error['username'] = $validItem;
            }
        }
        
        $validItem = $this->load('Validator')->validStringLength($params['password'], 5, 100);
        if($validItem == false){
            $error['password'] = $validItem;
        }

        $form = new CaptchaForm();
        if ($params) {
            //set captcha post
            $form->setData($params);

            if (!$form->isValid()) {
                $error['captcha'] = false;
            }
        }

        $ret = array(
            'status' => (count($error) > 0 ? false : true),
            'error' => $error
        );

        return $ret;
    }
}
