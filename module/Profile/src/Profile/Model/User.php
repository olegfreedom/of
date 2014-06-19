<?php
namespace Profile\Model;

class User extends \Application\Base\Model
{
    /**
     * Get User
     * @param int $id
     * @return array|null
     */
    public function getOne($id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if ($id > 0) {
            $select = $this->select()
                ->from(self::TABLE_USER)
                ->columns(array(
                    '*',
                    'birthday' => $this->expr('DATE_FORMAT(birthday_search, "%d.%m.%Y")'),
                ))
                ->where(array(
                    'userid' => $id
                ))
                ->limit(1);

            $user = $this->fetchRowSelect($select);

            if ( $user )
            {
                $select = $this->select()
                    ->from(self::TABLE_USERS_FIELDS)
                    ->columns(array('name', 'value'))
                    ->where(array(
                        'user_id' => $id
                    ));

                $result = $this->fetchSelect($select);

                if ( !empty($result) )
                {
                    foreach ($result as $item){
                        $user [$item ['name']] = $item ['value'];
                    }
                }

                $user ['area_id'] = 0;
                $user ['region_id'] = 0;
                $user ['city_id'] = 0;

                if ( !empty($user ['zip']) )
                {
                    $user ['city_id'] = $this->load('AdvertLocation', 'admin')->getCityByZIP($user ['zip']);
                    if ( !empty($user ['city_id']) )
                    {
                        $user ['area_id'] = $this->load('AdvertLocation', 'admin')->getAreaByCity($user ['city_id']);
                        if ( !empty($user ['area_id']) )
                        {
                            $user ['region_id'] = $this->load('AdvertLocation', 'admin')->getRegionByArea($user ['area_id']);
                        }
                    }
                }

                $user ['avatar_name'] = '';
                $user ['avatar_img'] = '';
                if ( !empty($user ['avatar']) )
                {
                    $user ['avatar_img'] = $this->getController()->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'user-avatar', 'id' => $user ['userid'], 'w' => 72, 'h' => 72, 'crop' => 'y'));
                    $avatar_name = explode('/', str_replace('\\', '/', $user ['avatar']));
                    $user ['avatar_name'] = end($avatar_name);
                }

                $ret = $user;
            }
        }

        return $ret;
    }

    /**
     * Edit User
     * @param array $params
     * @param int $id
     * @param null|\Base\Mvc\Controller $controller
     * @return bool|string
     */
    public function edit($params = null, $id = 0, $controller = null)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if (isset($params['username']) && $id > 0) {

            if ($this->checkLogin($params['username'], $id) == false) {

                $user = $this->getOne($id);

                $UsersFields = $this->load('UsersFields', 'admin');
                $fieldsAr = $UsersFields->customFieldsAr;

                if ( !empty($user) )
                {
                    $set = array();

                    if ( isset($user['username']) && isset($user['key']) && $user['username'] != $params['username'] )
                    {
                        if($controller !== null && $controller instanceof \Base\Mvc\Controller){
                            $changeUrl = $controller->easyUrl(
                                array('module' => 'profile', 'controller' => 'settings', 'action' => 'change-email', 'key' => $user['key']),
                                array('email' => $params['username'])
                            );
                            $this->load('SendEmail', 'admin')->changeEmail($user['username'], $changeUrl);
                            $ret = 'change-email';
                        }
                    }
                    if ( !empty($params ['password']) )
                    {
                        $this->load('Vbulletin', 'profile')->changePassword($user ['userid'], $params ['password']);
                        unset($params ['password']);
                    }

                    if ( !empty($params ['avatar']) )
                    {
                        if ( !empty($user ['avatar']) )
                        {
                            $this->load('Upload', 'admin')->unlink($user ['avatar']);
                        }

                        $set ['avatar'] = $this->load('Upload', 'admin')->save($params ['avatar'], array('gif', 'png', 'jpg', 'jpeg'), 'avatars/' . $user ['userid']);

                        unset($params ['avatar']);
                    }

                    if ( !empty($params ['birthday']) )
                    {
                        $this->load('Vbulletin', 'profile')->setBirthday($user ['userid'], $params ['birthday']);
                    }

                    foreach ( $fieldsAr as $field => $visibility )
                    {
                        if ( isset($params [$field]) && (!isset($user [$field]) || $user [$field] != $params [$field]) )
                        {
                            $set [$field] = $params [$field];
                        }
                    }

                    if ( !empty($set) ){
                        $result = $UsersFields->set($user ['userid'], $set);

                        $ret = $result;
/*
                        if ($result) {
                            $url = $controller->easyUrl(array('module' => 'profile','controller' => 'login', 'action'=>'login-by-key')).'key/'.$user['key'];
                            $this->load('SendEmail', 'admin')->changeData($params['username'], $params['password'], $url);
                            $ret = true;
                        }
*/
                    }
                }
            }
        }

        return $ret;
    }


    /**
     * Edit email
     * @param null $newEmail
     * @param null $key
     * @return bool
     */
    public function changeEmail($newEmail = null, $key = null)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($newEmail != null && $key != null) {
            $select = $this->select()
                ->from(self::TABLE_USER)
                ->columns(array(
                    'userid',
                    'username'
                ))
                ->where(array(
                    'key' => $key
                ))
                ->limit(1);
            $user = $this->fetchRowSelect($select);

            if ($user) {
                $id = $user['userid'];
                $set = array();
                $set['username'] = $newEmail;
                $update = $this->update(self::TABLE_USER)
                    ->set($set)
                    ->where(array('userid' => $id));
                $result = $this->execute($update);

                if ($result) {
                    $this->load('SendEmail', 'admin')->changeEmailSuccess($newEmail);
                    $ret = true;
                }
            }
        }
        return $ret;
    }

    /**
     * Get id by login(username)
     * @param string $login
     * @return bool|mixed
     */
    public function getIdByUserName($login = ''){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if (!empty($login)){
            $select = $this->select()
                ->from(self::TABLE_USER)
                ->columns(array('userid'))
                ->where(array('username' => $login))
                ->limit(1);
            $result = (int)$this->fetchOneSelect($select);

            if ($result > 0){
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * Get user's list
     * @param string $username
     * @param int $userId
     * @return bool|mixed
     */
    public function getUsersList($username = '', $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if (!empty($username)){
            $select = $this->select()
                ->from(array('u' => self::TABLE_USER))
                ->columns(array('username'))
                ->join(array('m_f' => self::TABLE_MESSAGES),
                    'm_f.from_user_id = u.id',
                     array())
                ->where(array($this->where()
                    ->like('u.username', '%' . $username . '%')
                    ->notEqualTo('u.id', $userId)
                    ->andPredicate(
                        $this->where()
                        ->equalTo('m_f.from_user_id', $userId)
                        ->or
                        ->equalTo('m_f.to_user_id', $userId)

                        )
                    )
                )
                ->group('username');

            $result = $this->fetchSelect($select);

            if ($result > 0){
                foreach ($result as $item){
                    $ret[] = $item['username'];
                }
            }
        }
        return $ret;
    }
    /**
     * Check is there this username
     * @param string $login
     * @param int $id
     * @return bool
     */
    public function checkLogin($login = null, $id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($login !== null) {
            $select = $this->select()
                ->from(self::TABLE_USER)
                ->columns(array($this->expr('COUNT(*)')))
                ->where(array(
                    'username' => $login
                ))
                ->limit(1);
            if ($id > 0) {
                $select->where(array(
                    $this->where()
                        ->notEqualTo('userid', $id)
                ));
            }

            $result = (int)$this->fetchOneSelect($select);

            if ($result > 0) {
                $ret = true;
            }
        }

        return $ret;
    }

    /**
     * Generate salt string
     * @return string
     */
    public function generateSalt()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        return $this->randString(rand(5, 10));
    }

}