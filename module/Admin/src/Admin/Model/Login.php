<?php
namespace Admin\Model;

class Login extends \Application\Base\Model
{
    /**
     * Authorize User
     * @param array $params
     * @return null|array
     */
    public function authUser($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        if(isset($params['username']) && isset($params['password'])){
            $select = $this->select()
                           ->from(self::TABLE_USER)
                           ->columns(array(
                               'userid',
                               'username'
                           ))
                           ->where(array(
                               'username' => $params['username'],
                               'password' => $this->expr('md5(concat(?,salt))', $params['password']),
                               'usergroupid' => self::USERS_LEVEL_ADMIN
                           ))
                           ->limit(1);
            //$this->debug($this->getSqlString($select));
            $result = $this->fetchRowSelect($select);

            if($result){
                $ret = $result;
            }
        }
        
        return $ret;
    }
}