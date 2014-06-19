<?php
namespace Profile\Model;

class Forgot extends \Application\Base\Model
{
    /**
     * Recover User
     * @param array $params
     * @return bool
     */
    public function recover($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if(isset($params['username'])){
            $select = $this->select()
                           ->from(self::TABLE_USER)
                           ->columns(array('id'))
                           ->where(array(
                               'username' => $params['username'],
                               'level' => self::USERS_LEVEL_USER
                           ))
                           ->limit(1);
            
            $userId = (int)$this->fetchOneSelect($select);
            
            if($userId > 0){
                $newPassword = $this->randString(8);
                $salt = $this->load('User', 'profile')->generateSalt();

                $update = $this->update(self::TABLE_USER)
                               ->set(array(
                                   'password' => $this->expr('md5(?)', $newPassword.$salt),
                                   'salt' => $salt,
                                   'key' => $this->expr('md5(?)', $params['username'].$salt)
                               ))
                               ->where(array(
                                   'id' => $userId,
                                   'level' => self::USERS_LEVEL_USER
                               ));

                $result = $this->execute($update);

                if($result){
                    $ret = true;
                    $this->load('SendEmail', 'admin')->forgotPassword($params['username'], $newPassword);
                }
            }
        }
        
        return $ret;
    }

    /**
     * Get activation key by username
     * @param string $username
     * @return string|bool
     */
    public function getKeyByUsername($username = null)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = FALSE;

        if ( !empty($username) )
        {
            $select = $this->select()
                ->from(self::TABLE_USER)
                ->columns(array(
                    'key'
                ))
                ->where(array(
                    'username' => $username,
                    'level' => self::USERS_LEVEL_USER
                ))
                ->limit(1);

            $key = $this->fetchOneSelect($select);

            if ( !empty($key) )
            {
                $ret = $key;
            }
        }

        return $ret;
    }
    
    /**
     * Query recover user email
     * @param array $params
     * @return bool
     */
    public function queryRecovery($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = FALSE;

        $userKey = $this->getKeyByUsername($params['username']);
        $param = array(
            'key' => $userKey,
            'username' => $params['username']
        );
        if ( !empty($userKey) )
        {
            $this->addKeyTemp($param);
            $this->load('SendEmail', 'admin')->recoveryConfirmation($params, $userKey);
            $ret = TRUE;
        }
        
        return $ret;
    }

    /**
     * Get username by key
     * @param string $key
     * @return string
     */
    public function getUserByKey($key = null) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        $select = $this->select()
                       ->from(self::TABLE_USER)
                       ->columns(array(
                           'id',
                           'username'
                           ))
                       ->where(array(
                           'key' => $key 
                       ))
                       ->limit(1);
        
        $result = $this->fetchRowSelect($select);
            
        if ($result) {
            $ret = $result;
        }
        
        return $ret;
        
    }

    /**
     * Clear table users_key_temp
     * @param $key
     * @return bool
     */
    public function clearKeyTemp($minute){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $minute = (string) $minute;

        if(isset($minute)){
            $delete = $this->delete(self::TABLE_USER_KEY_TEMP)
                           ->where(array(
                                $this->where()
                                ->lessThanOrEqualTo('timestamp', $this->expr('DATE_SUB(NOW(), INTERVAL '.$minute.' MINUTE)'))
                            ));

            $ret = $this->execute($delete);
        }

        return  $ret;
    }

    /**
     * Add params in the table users_key_temp
     * @param $params
     * @return bool
     */
    public function addKeyTemp($params){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if(isset($params)){
            $insert = $this->insert(self::TABLE_USER_KEY_TEMP)
                           ->values($params);

            $ret = $this->execute($insert);
        }

        return (bool) $ret;
    }

    public function getKeyTemp($key){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if(isset($key)){
            $select = $this->select()
                           ->from(self::TABLE_USER_KEY_TEMP)
                           ->columns(array('key'))
                           ->where(array(
                                    'key' => $key
                           ));
            $ret = $this->fetchRowSelect($select);
        }

        return $ret;
    }
}