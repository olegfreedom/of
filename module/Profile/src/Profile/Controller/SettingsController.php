<?php
namespace Profile\Controller;

class SettingsController extends \Profile\Base\Controller
{

    const USER_EDIT_SUCCESS_MESSAGE = 'Профайл сохранён.';
    const USER_EDIT_ERROR_MESSAGE = 'Профайл НЕ сохранён. Повторите попытку позже.';

    const INVALID_OLD_PASSWORD_ERROR_MESSAGE = 'Неверно введён старый пароль.';
    const INVALID_ZIP_ERROR_MESSAGE = 'Извините, но данному индексу не соответствует ни один населённый пункт. Проверьте правильность индекса или заполните поля ниже.';

    public function __construct(){
        parent::__construct();
        
        $this->pushTitle('Настройки профиля');
    }
    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $user = $this->load('User', 'profile')->getOne($this->getUserId());

        $ret = array(
            'getEdit' => $user,
            'regionList' => $this->load('AdvertLocation', 'admin')->getRegions(),
        );
        
        return $this->view($ret);
    }
    
    public function editAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        if($this->p_int('settings-form') === 1){

            $zip = $this->p_string('zip');
            $locationAr = $this->load('Location', 'admin')->getLocationByZip($zip);

            $params = array(
                'username' => $this->p_string('username'),
                'old_password' => $this->p_string('old_password'),
                'password' => $this->p_string('password'),
                'retry_password' => $this->p_string('retry_password'),

                'secondname' => $this->p_string('secondname'),
                'firstname' => $this->p_string('firstname'),
                'lastname' => $this->p_string('lastname'),
                'birthday' => $this->p_string('birthday'),
                'passport_series' => $this->p_string('passport_series'),
                'passport_data' => $this->p_string('passport_data'),
                'passport_receive_date' => $this->p_string('passport_receive_date'),
                'inn' => $this->p_string('inn'),
                'address' => $this->p_string('address'),
                'website' => $this->p_string('website'),

                'phone1' => $this->p_string('phone1'),
                'phone2' => $this->p_string('phone2'),
                'fax' => $this->p_string('fax'),

                'region_id' => $locationAr ['region_id'],
                'area_id' => $locationAr ['area_id'],
                'city_id' => $locationAr ['city_id'],
                'zip' => $zip,
                'avatar' => $this->getFiles('avatar'),
                'about' => $this->p_string('about'),
            );

            $check = $this->check($params);

            if($check['status'] == true){

                $res = $this->load('User', 'profile')->edit($params, $this->getUserId(), $this);
                if($res === true){

                    $this->flashMessenger()->addSuccessMessage(self::USER_EDIT_SUCCESS_MESSAGE);

                    return $this->redirect()
                                 ->toUrl(
                                     $this->easyUrl(array('action' => 'index'))
                                 );
                }else if($res === 'change-email'){
                    return $this->redirect()
                        ->toUrl(
                            $this->easyUrl(array('action' => 'email-activate'))
                        );
                } else {

                    $this->flashMessenger()->addErrorMessage(self::USER_EDIT_ERROR_MESSAGE);

                    $dump = $this->getLogStr($this->getUserId(), '$this->getUserId()') . "\n" .$this->getLogStr($params, '$params');

                    $this->logEvent('User edit error, dump:' . "\n" . $dump);

                    return $this->redirect()
                        ->toUrl(
                            $this->easyUrl(array('action' => 'index'))
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

    public function emailActivateAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $ret = array();

        return $this->view($ret);
    }

    public function changeEmailAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $newEmail = $this->p_string('email');
        $key = $this->p_string('key');

        if ($newEmail !== null && $key !== null){
            $change = $this->load('User', 'profile' )->changeEmail($newEmail, $key);
            if ($change){
                $this->setUserNаme($newEmail);
                return $this->redirect()
                    ->toUrl(
                        $this->easyUrl(array('action' => 'success'))
                    );
            }
        }
        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'error'))
            );
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
            'username' => $this->p_string('username'),
            'secondname' => $this->p_string('secondname'),
            'firstname' => $this->p_string('firstname'),
            'lastname' => $this->p_string('lastname'),
            'address' => $this->p_string('address'),
            'old_password' => $this->p_string('old_password'),
            'password' => $this->p_string('password'),
            'retry_password' => $this->p_string('retry_password'),
            'phone1' => $this->p_string('phone1'),

            'region_id' => $this->p_int('region_id'),
            'area_id' => $this->p_int('area_id'),
            'city_id' => $this->p_int('city_id'),
            'zip' => $this->p_string('zip'),
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

        $fieldsCheckAr = array('lastname', 'firstname', 'secondname', 'address', 'username', 'zip');
        foreach ( $fieldsCheckAr as $fieldName )
        {
            $Validator->mainCheck($fieldName, $params, $errorAr, $messagesAr);
        }

        if ( !empty($params ['password']) )
        {
            $field = 'old_password';
            if ( !$Validator->validStringLength($params [$field], 5, 100) )
            {
                $errorAr [$field] = FALSE;
                $messagesAr [$field] = $Validator->getErrors();
            }
            else
            {
                $oldDataAr = array(
                    'username' => $this->getUserNаme(),
                    'password' => $params ['old_password'],
                );

                $user = $this->load('Login', 'profile')->authUser($oldDataAr);
                if ( empty($user) )
                {
                    $errorAr [$field] = FALSE;
                    $messagesAr [$field] = array(self::INVALID_OLD_PASSWORD_ERROR_MESSAGE);
                }
                else
                {
                    $field = 'retry_password';
                    if ( !$Validator->validIdentical($params['password'], $params[$field]) )
                    {
                        $errorAr [$field] = FALSE;
                        $messagesAr [$field] = $Validator->getErrors();
                    }
                    elseif ( !$Validator->validStringLength($params ['password'], 5, 100) )
                    {
                        $errorAr ['password'] = FALSE;
                        $messagesAr ['password'] = $Validator->getErrors();
                    }
                }
            }
        }

        $ret = array(
            'status' => empty($errorAr),
            'error' => $errorAr,
            'messages' => $messagesAr,
        );
        
        return $ret;
    }
}
