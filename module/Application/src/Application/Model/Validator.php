<?php
namespace Application\Model;

class Validator extends \Application\Base\Model
{
    private $lastErrorsAr = array();

    const USER_EXISTS_ERROR_MESSAGE = 'Такой Email уже существует. Выберите другой.';
    const INVALID_ZIP_ERROR_MESSAGE = 'Извините, но данному индексу не соответствует ни один населённый пункт. Проверьте правильность индекса или заполните поля ниже.';


    /**
     * Validate Email
     * @param string $val
     * @return bool
     */
    public function validEmail($val = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\EmailAddress();
            $validator->setMessages(array(
                \Zend\Validator\EmailAddress::INVALID            => 'Неверный тип поля.',
                \Zend\Validator\EmailAddress::INVALID_FORMAT     => 'Неправильный формат электронной почты.',
                \Zend\Validator\EmailAddress::INVALID_HOSTNAME   => 'Неправильный формат электронной почты.',
                \Zend\Validator\EmailAddress::INVALID_MX_RECORD  => 'Неправильный формат электронной почты.',
                \Zend\Validator\EmailAddress::INVALID_SEGMENT    => 'Неправильный формат электронной почты.',
                \Zend\Validator\EmailAddress::DOT_ATOM           => 'Неправильный формат электронной почты.',
                \Zend\Validator\EmailAddress::QUOTED_STRING      => 'Неправильный формат электронной почты.',
                \Zend\Validator\EmailAddress::INVALID_LOCAL_PART => 'Неправильный формат электронной почты.',
                \Zend\Validator\EmailAddress::LENGTH_EXCEEDED    => 'Превышена допустимая длина электронной почты.',
            ))->useDomainCheck(FALSE);

            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }

        return $ret;
    }
    
    /**
     * Validate Hostname
     * @param string $val
     * @return bool
     */
    public function validHostname($val = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\Hostname();
            $validator->setMessages(array(
                \Zend\Validator\Hostname::CANNOT_DECODE_PUNYCODE  => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::INVALID                 => 'Неверный тип поля.',
                \Zend\Validator\Hostname::INVALID_DASH            => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::INVALID_HOSTNAME        => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::INVALID_HOSTNAME_SCHEMA => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::INVALID_LOCAL_NAME      => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::INVALID_URI             => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::IP_ADDRESS_NOT_ALLOWED  => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::LOCAL_NAME_NOT_ALLOWED  => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::UNDECIPHERABLE_TLD      => 'Неправильный формат для вебсайта.',
                \Zend\Validator\Hostname::UNKNOWN_TLD             => 'Неправильный формат для вебсайта.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate Digits
     * @param int|float $val
     * @return bool
     */
    public function validDigits($val = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\Digits();
            $validator->setMessages(array(
                \Zend\Validator\Digits::NOT_DIGITS   => 'Значение должно быть числом.',
                \Zend\Validator\Digits::STRING_EMPTY => 'Заполните обязательное поле.',
                \Zend\Validator\Digits::INVALID      => 'Неверный тип поля.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate InArray
     * @param string $val
     * @param array $array
     * @return bool
     */
    public function validInArray($val = null, $array = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null && sizeof($array) > 0){
            $validator = new \Zend\Validator\InArray(array('haystack' => $array));
            $validator->setMessages(array(
                \Zend\Validator\InArray::NOT_IN_ARRAY => 'Значение не найдено в числе допустимых.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate Ip
     * @param string $val
     * @return bool
     */
    public function validIp($val = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\Ip();
            $validator->setMessages(array(
                \Zend\Validator\Ip::INVALID        => 'Неверный тип поля.',
                \Zend\Validator\Ip::NOT_IP_ADDRESS => 'Неверное значение IP-адреса.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate NotEmpty
     * @param string $val
     * @return bool
     */
    public function validNotEmpty($val = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\NotEmpty();
            $validator->setMessages(array(
                \Zend\Validator\NotEmpty::IS_EMPTY => 'Заполните обязательное поле.',
                \Zend\Validator\NotEmpty::INVALID => 'Неправильный тип поля.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate Regex
     * @param string $val
     * @param regexp $pattern
     * @return bool
     */
    public function validRegex($val = null, $pattern = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null && $pattern !== null){
            $validator = new \Zend\Validator\Regex($pattern);
            $validator->setMessages(array(
                \Zend\Validator\Regex::INVALID   => 'Неверный тип поля.',
                \Zend\Validator\Regex::NOT_MATCH => 'Поле не заполнено или имеет неправильный шаблон.',
                \Zend\Validator\Regex::ERROROUS  => 'Внутренняя ошибка сравнения поля.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate StringLength
     * @param string $val
     * @param int $min
     * @param int $max
     * @return bool
     */
    public function validStringLength($val = null, $min = 1, $max = 100){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\StringLength(array('min' => $min, 'max' => $max));
            $validator->setMessages(array(
                \Zend\Validator\StringLength::INVALID   => 'Неправильный тип поля.',
                \Zend\Validator\StringLength::TOO_SHORT => 'Минимум %min% символов для этого поля.',
                \Zend\Validator\StringLength::TOO_LONG  => 'Максимум %max% символов для этого поля.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate Between
     * @param string $val
     * @param int $min
     * @param int $max
     * @param bool $inclusive
     * @return bool
     */
    public function validBetween($val = null, $min = 0, $max = 100, $inclusive = true){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\Between(array('min' => $min, 'max' => $max, 'inclusive' => $inclusive));
            $validator->setMessages(array(
                \Zend\Validator\Between::NOT_BETWEEN        => 'Введите значение от %min% до %max% включительно.',
                \Zend\Validator\Between::NOT_BETWEEN_STRICT => 'Введите значение между %min% и %max%, невключительно.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate GreaterThan
     * @param string $val
     * @param int $min
     * @param bool $inclusive
     * @return bool
     */
    public function validGreaterThan($val = null, $min = 0, $inclusive = false){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\GreaterThan(array('min' => $min, 'inclusive' => $inclusive));
            $validator->setMessages(array(
                \Zend\Validator\GreaterThan::NOT_GREATER => 'Введите значение больше %min%.',
                \Zend\Validator\GreaterThan::NOT_GREATER_INCLUSIVE => 'Введите значение большее или равное %min%.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate LessThan
     * @param string $val
     * @param int $max
     * @param bool $inclusive
     * @return bool
     */
    public function validLessThan($val = null, $max = 100, $inclusive = false){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($val !== null){
            $validator = new \Zend\Validator\LessThan(array('max' => $max, 'inclusive' => $inclusive));
            $validator->setMessages(array(
                \Zend\Validator\LessThan::NOT_LESS => 'Введите значение меньше %max%.',
                \Zend\Validator\LessThan::NOT_LESS_INCLUSIVE => 'Введите значение меньшее или равное %max%.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }
    
    /**
     * Validate Identical
     * @param string $origin
     * @param string $val
     * @return bool
     */
    public function validIdentical($origin = null, $val = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->lastErrorsAr = array();
        
        if($origin !== null && $val !== null){
            $validator = new \Zend\Validator\Identical($origin);
            $validator->setMessages(array(
                \Zend\Validator\Identical::NOT_SAME => 'Поля должны совпадать.',
                \Zend\Validator\Identical::MISSING_TOKEN => 'Не введено значение исходного поля.',
            ));
            
            if($validator->isValid($val)){
                $ret = true;
            }
            else
            {
                $this->lastErrorsAr = $validator->getMessages();
            }
        }
        
        return $ret;
    }

    /**
     * Get last error messages
     * @return array
     */
    public function getErrors()
    {
        return $this->lastErrorsAr;
    }

    private function setError($fieldName, &$errorAr, &$messagesAr)
    {
        $errorAr [$fieldName] = FALSE;
        $messagesAr [$fieldName] = $this->getErrors();
    }

    public function mainCheck($fieldName, $params, &$errorAr, &$messagesAr)
    {
        switch ( $fieldName )
        {
            case 'agree':
                if ( !$this->validNotEmpty($params [$fieldName]) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'category_id':
            case 'type_id':
                if ( !$this->validGreaterThan($params [$fieldName]) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'description_full':
                if ( !$this->validStringLength($params [$fieldName], 1, 500) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'comment_full':
                if ( !$this->validStringLength($params [$fieldName], 5, 500) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'name':
            case 'lastname':
            case 'firstname':
            case 'secondname':
                if ( !$this->validStringLength($params [$fieldName], 2, 50) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'username':
                if ( !$this->validEmail($params [$fieldName]) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
                elseif ( $this->load('User', 'profile')->checkLogin($params [$fieldName], $this->getController()->getUserId()) )
                {
                    $errorAr [$fieldName] = FALSE;
                    $messagesAr [$fieldName] = array(self::USER_EXISTS_ERROR_MESSAGE);
                }
            break;


            case 'address':
                if ( !$this->validStringLength($params [$fieldName], 1, 255) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'password':
                if ( !$this->validStringLength($params [$fieldName], 6, 100) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'retry_password':
                if ( !$this->validStringLength($params [$fieldName], 6, 100) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
                elseif ( !$this->validIdentical($params ['password'], $params [$fieldName]) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'zip':
                if ( !$this->validNotEmpty($params [$fieldName]) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
                elseif ( !($locationAr = $this->load('Location', 'admin')->getLocationByZip($params [$fieldName])) )
                {
                    $errorAr [$fieldName] = FALSE;
                    $messagesAr [$fieldName] = array(self::INVALID_ZIP_ERROR_MESSAGE);
                }
            break;

            case 'type':
                $typesAvailAr = array(\Admin\Model\Users::TYPE_PHYSICAL, \Admin\Model\Users::TYPE_LEGAL);
                if ( !$this->validInArray($params [$fieldName], $typesAvailAr) )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

            case 'price':
                if ( !$this->validRegex($params ['price'], '/^[0-9][0-9\,\.]+$/') )
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
                elseif ( !$this->validBetween($params ['price'], 1, 9999999))
                {
                    $this->setError($fieldName, $errorAr, $messagesAr);
                }
            break;

        }


    }
}