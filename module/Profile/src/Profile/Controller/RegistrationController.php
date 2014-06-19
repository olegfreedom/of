<?php
namespace Profile\Controller;

use Profile\Model\CaptchaForm;

class RegistrationController extends \Profile\Base\Controller
{
    const USER_REGISTRATION_SUCCESS_MESSAGE = 'Вы успешно зарегистрировались.';

    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $referrer = $this->p_string('referrer');

        if(isset($this->session->auth['id'])){
            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array())
                         );
        }
        if($this->p_int('registration-form') === 1){
            $zip = $this->p_string('zip');
            $locationAr = $this->load('Location', 'admin')->getLocationByZip($zip);

            $params = array(
                'username' => $this->p_string('username'),
                'secondname' => $this->p_string('secondname'),
                'firstname' => $this->p_string('firstname'),
                'lastname' => $this->p_string('lastname'),
                'address' => $this->p_string('address'),
                'password' => $this->p_string('password'),
                'retry_password' => $this->p_string('retry_password'),
                'activation_url' => $this->easyUrl(array('action' => 'activation', 'key' => '_SET_KEY_', 'referrer' => $referrer)),
                //'type' => $this->p_string('type'),

                'region_id' => $locationAr ['region_id'],
                'area_id' => $locationAr ['area_id'],
                'city_id' => $locationAr ['city_id'],
                'zip' => $zip,
                'agree' => $this->p_string('agree'),
            );

            $check = $this->check($params);

            $userId = NULL;
            if($check['status'] == true){
                $userId = $this->load('Registration', 'profile')->authUser($params);
            }

            return $this->redirect()
                         ->toUrl(
                             $this->easyUrl(array('action' => 'confirm', 'id' => $userId))
                         );
        }
        //create captcha
        $form = new CaptchaForm();
        return $this->view(array(
            'form' => $form,
            'referrer' => $referrer,
            'regionList' => $this->load('Location', 'admin')->getRegions()
        ));
        
    }

    public function confirmAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $userId = $this->p_int('id');

        $ret = array(
            'user_email' => '',
        );

        if ( !empty($userId) )
        {
            $ret ['user_email'] = $this->load('Users', 'admin')->getUsername($userId);
        }

        return $this->view($ret);
    }

    public function activationAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $key = $this->p_string('key');
        $referrer = $this->p_string('referrer');
        $keyTemp = $this->load('Forgot', 'profile')->getKeyTemp($key);

        if (!empty($key) && $key == $keyTemp['key']) {
            $user = $this->load('Registration', 'profile')->activationUser($key);
            if ($user != null){
                $this->setUserId($user['userid']);
                $this->setUserNаme($user['username']);

                $this->load('Forgot', 'profile')->clearKeyTemp(0);

                $this->flashMessenger()->addSuccessMessage(self::USER_REGISTRATION_SUCCESS_MESSAGE);

                if ( !empty($referrer) )
                {
                    return $this->redirect()
                        ->toUrl(str_replace('__', '/', $referrer));
                }
                else
                {
                    return $this->redirect()
                                 ->toUrl(
                                     $this->easyUrl(array('controller' => 'settings'))
                                 );
                }
            }
        }

        $dump = $this->getLogStr($key, '$key') . "\n" .$this->getLogStr($referrer, '$referrer');
        $this->logEvent('User registration error, dump:' . "\n" . $dump);

        return $this->redirect()
                    ->toUrl(
                        $this->easyUrl(array('controller' => 'error', 'action' => 'registration'))
                    );
    }

    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'username' => $this->p_string('username'),
            'secondname' => $this->p_string('secondname'),
            'firstname' => $this->p_string('firstname'),
            'lastname' => $this->p_string('lastname'),
            'address' => $this->p_string('address'),
            'password' => $this->p_string('password'),
            'retry_password' => $this->p_string('retry_password'),
            // 'type' => $this->p_string('type'),

            'region_id' => $this->p_int('region_id'),
            'area_id' => $this->p_int('area_id'),
            'city_id' => $this->p_int('city_id'),
            'zip' => $this->p_string('zip'),
            'agree' => $this->p_string('agree'),
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

        $errorAr = array();
        $messagesAr = array();
        $Validator = $this->load('Validator');
        $locationAr = array();
        $this->getUserId();

        $form = new CaptchaForm();
        if ($params) {
            //set captcha post
            $form->setData($params);

            if ($form->isValid()) {
                $retError = array(
                    'captcha' => true
                );
            }else{
                $retError = array(
                    'captcha' => false
                );
            }
        }


        $fieldsCheckAr = array('lastname', 'firstname', 'secondname', 'address', 'username', 'password', 'retry_password', 'zip', 'agree'); // , 'type'
        foreach ( $fieldsCheckAr as $fieldName )
        {
            $Validator->mainCheck($fieldName, $params, $errorAr, $messagesAr);
        }

            /*
            $zipAvailAr = array();
            if ( !empty($zipAr) )
            {
                foreach ( $zipAr as $row )
                {
                    $zipAvailAr [] = $row ['name'];
                }
                $validItem = $this->load('Validator')->validInArray($params['zip'], $zipAvailAr);
                if($validItem == false){
                    $error['zip'] = $validItem;
                }
            }
            */

        /*
        $validItem = $this->load('Validator')->validGreaterThan($params['region_id']);
        if($validItem == false){
            $error['region_id'] = $validItem;
        }else{
            $validItem = $this->load('Validator')->validGreaterThan($params['area_id']);
            if($validItem == false){
                $error['area_id'] = $validItem;
            }else{
                $validItem = $this->load('Validator')->validGreaterThan($params['city_id']);
                if($validItem == false){
                    $error['city_id'] = $validItem;
                }else{
                    $validItem = $this->load('Validator')->validNotEmpty($params['zip']);
                    if($validItem == false){
                        $error['zip'] = $validItem;
                    }else{
                        $zipAr = $this->load('AdvertLocation', 'admin')->getZipByCity($params['city_id']);
                        $zipAvailAr = array();
                        if ( !empty($zipAr) )
                        {
                            foreach ( $zipAr as $row )
                            {
                                $zipAvailAr [] = $row ['name'];
                            }
                            $validItem = $this->load('Validator')->validInArray($params['zip'], $zipAvailAr);
                            if($validItem == false){
                                $error['zip'] = $validItem;
                            }
                        }
                    }
                }
            }

        }
        */



        $ret = array(
            'status' => empty($errorAr),
            'error' => (is_array($retError)) ? (array_merge($errorAr, $retError)) : $errorAr,
            'messages' => $messagesAr,
            'location' => $locationAr,
        );

        return $ret;
    }
}
