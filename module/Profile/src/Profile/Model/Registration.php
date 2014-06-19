<?php
namespace Profile\Model;

class Registration extends \Application\Base\Model
{
    const VISIBILITY_ALL = 0;
    const VISIBILITY_FRIENDS = 1;
    const VISIBILITY_NOBODY = 2;

    private $customFieldsAr = array(
        'key' => self::VISIBILITY_NOBODY,
        'zip' => self::VISIBILITY_FRIENDS,
        'secondname' => self::VISIBILITY_FRIENDS,
        'firstname' => self::VISIBILITY_ALL,
        'lastname' => self::VISIBILITY_FRIENDS,
        'address' => self::VISIBILITY_FRIENDS,
        'type' => self::VISIBILITY_ALL,
        /*
        'passport_series' => self::VISIBILITY_NOBODY,
        'passport_data' => self::VISIBILITY_NOBODY,
        'passport_receive_date' => self::VISIBILITY_NOBODY,
        'inn' => self::VISIBILITY_NOBODY,
        */
    );


    /**
     * Authorize User
     * @param array $params
     * @param bool $guest
     * @return null|array
     */
    /*
        public function authUser($params = null, $guest = false){
            $this->log(__CLASS__ . '\\' . __FUNCTION__);

            $ret = null;

            if (
                isset($params['username']) && isset($params['password']) && isset($params['type']) && isset($params['activation_url']) &&
                isset($params['secondname']) && isset($params['firstname']) && isset($params['lastname']) && isset($params['address'])
            ){
                if($this->load('User', 'profile')->checkLogin($params['username']) == false){
                    $salt = $this->load('User', 'profile')->generateSalt();

                    $insert = $this->insert(self::TABLE_USER)
                                   ->values(array(
                                       'username' => $params['username'],
                                       'secondname' => $params['secondname'],
                                       'firstname' => $params['firstname'],
                                       'lastname' => $params['lastname'],
                                       'address' => $params['address'],
                                       'password' => $this->expr('md5(?)', $params['password'].$salt),
                                       'type' => $params['type'],
                                       'salt' => $salt,
                                       'level' => self::USERS_LEVEL_USER,
                                       'timestamp' => $this->load('Date', 'admin')->getDateTime(),
                                       'key' => $this->expr('md5(?)', $params['username'].$salt),
                                       'city_id' => $params['city_id'],
                                       'zip' => $params['zip'],
                                   ));

                    $result = $this->execute($insert);

                    $ret = $this->insertId($result);

                    if ($result && $guest === true){
                        $key = md5($params['username'].$salt);
                        $this->load('SendEmail', 'admin')->guestActivate($params['username'], $params['password'], $key, $params['activation_url']);
                    }elseif($result){
                        $key = md5($params['username'].$salt);
                        $this->load('SendEmail', 'admin')->activationUser($params['username'], $key, $params['activation_url']);
                    }

                    if($result)
                    {
                        $this->addForumUser($params);
                    }
                }
            }

            return $ret;
        }
    */
    public function authUser($params = null, $guest = false){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if (
            isset($params['username']) && isset($params['password']) && isset($params['activation_url']) &&
            isset($params['secondname']) && isset($params['firstname']) && isset($params['lastname']) && isset($params['address'])
        ){
            if($this->load('User', 'profile')->checkLogin($params['username']) == false){

                $userId = $this->addForumUser($params);

                if ( !empty($userId) )
                {
                    $salt = $this->load('User', 'profile')->generateSalt();
                    $params ['key'] = md5($params ['username'] . $salt);
                    $UsersFields = $this->load('UsersFields', 'admin');
                    $paramsForTemp = array(
                        'key' => $params['key'],
                        'username' => $params['username']
                    );
                    foreach ( $this->customFieldsAr as $fieldName => $fieldVisibility )
                    {
                        $UsersFields->set($userId, $fieldName, $params [$fieldName]);
                    }

                    if ( !empty($guest) )
                    {
                        $this->load('Forgot', 'profile')->addKeyTemp($paramsForTemp);
                        $this->load('SendEmail', 'admin')->guestActivate($params ['username'], $params ['password'], $params ['key'], $params ['activation_url']);
                    }
                    else
                    {
                        $this->load('Forgot', 'profile')->addKeyTemp($paramsForTemp);
                        $this->load('SendEmail', 'admin')->activationUser($params ['username'], $params ['key'], $params ['activation_url']);
                    }

                    $ret = $userId;
                }

            }
        }

        return $ret;
    }


        /**
         * Activation User
         * @param string $key
         * @return null|array
         */
    public function activationUser($key = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if ($key !== null) {
            $Vbulletin = $this->load('Vbulletin', 'profile');
            $select = $this->select(array('u' => self::TABLE_USER))
                           ->columns(array(
                               'userid',
                               'username'
                           ))
                            ->join(
                                array('uf' => self::TABLE_USERS_FIELDS),
                                $this->expr('uf.user_id = u.userid AND uf.name = ?', array('key')),
                                array()
                            )
                           ->where(array(
                               'uf.value' => $key,
                           ))
                           ->limit(1);

            $user = $this->fetchRowSelect($select);

            if ( !empty($user) && $user['usergroupid'] == $Vbulletin::NOACTIVATION_USERGROUP )
            {
                $result = $Vbulletin->activateUser($user ['userid']);

                if ($result){
                    $this->load('SendEmail', 'admin')->registration($user ['username']);
                }
            }
            if ($user) {
                $ret = $user;
            }
        }
        
        return $ret;
    }

    private function addForumUser($paramsAr)
    {
        $Vbulletin = $this->load('Vbulletin', 'profile');

        $forumUserAr = array(
            'username' => $paramsAr ['username'],
            'email' => $paramsAr ['username'],
            'password' => $paramsAr ['password'],
        );

        $ret = $Vbulletin->registerNewUser($forumUserAr);

        return $ret;
    }

}