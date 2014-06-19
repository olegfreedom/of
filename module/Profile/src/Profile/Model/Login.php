<?php
namespace Profile\Model;

class Login extends \Application\Base\Model
{
    /**
     * Authorize User
     * @param array $params
     * @return null|array
     */
    public function authUser($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $Vbulletin = $this->load('Vbulletin', 'profile');

        return $Vbulletin->authUser($params);
    }
}