<?php
namespace Admin\Model;

class Users extends \Application\Base\Model
{
    const USERS_PER_PAGE = 20;
    const TYPE_PHYSICAL = 'p';
    const TYPE_LEGAL = 'l';
    
    private static $userLevel = array();

    private function getSQL(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $select = $this->select()
            ->from(array('u' => self::TABLE_USER))
            ->columns(array())
            ->join(
                array('lc' => self::TABLE_LOCATION_CITIES),
                'lc.id = u.city_id',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('la' => self::TABLE_LOCATION_AREAS),
                'la.id = lc.area_id',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('lr' => self::TABLE_LOCATION_REGIONS),
                'lr.id = la.region_id',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('ur' => self::TABLE_USERS_RATING),
                'ur.user_id = u.userid',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->addJoinColumns('lc', array(
                'city_id' => 'id',
                'city' => 'name'
            ))
            ->addJoinColumns('la', array(
                'area_id' => 'id',
                'area' => 'name'
            ))
            ->addJoinColumns('lr', array(
                'region_id' => 'id',
                'region' => 'name'
            ))
        ;

        return $select;
    }

    /**
     * Get user list
     * @param int $page
     * @return null|array
     */
    public function getList($whereAr = array()){ // $page = 0
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;

        $select = $this->getSQL();

        if ( $select instanceof \Zend\Db\Sql\Select )
        {
            $select
               ->from(array('u' => self::TABLE_USER))
               ->columns(array(
                   'userid',
                   'username',
                   'email',
                   'status',
                   'joindate' => $this->expr('FROM_UNIXTIME(u.joindate, "%d.%m.%Y %H:%i")')
               ))
               ->addJoinColumns('lc', array(
                    'city_id' => 'id',
                    'city' => 'name'
               ))
               ->addJoinColumns('la', array(
                    'area_id' => 'id',
                    'area' => 'name'
               ))
               ->addJoinColumns('lr', array(
                    'region_id' => 'id',
                    'region' => 'name'
               ))
               ->group('u.userid')
               ->order('u.joindate desc');

            if ( !empty($whereAr ['page']) && (int) $whereAr ['page'] > 0 ){
                $select->limitPage((int) $whereAr ['page'], self::USERS_PER_PAGE);
                unset($whereAr ['page']);
            }

            $select->where(array_merge((array)$whereAr
//                array(
//                    'u.usergroupid' => self::USERS_LEVEL_USER
//                )
            ));
            $select->where($this->where()
                                ->notEqualTo('u.usergroupid', self::USERS_LEVEL_ADMIN)
            );

            $result = $this->fetchSelect($select);

            if($result){
                foreach ( $result as &$user )
                {
                    $user ['avatar_name'] = '';
                    $user ['avatar_img'] = '';
                    if ( !empty($user ['avatar']) )
                    {
                        $user ['avatar_img'] = $this->getController()->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'user-avatar', 'id' => $user ['id'], 'w' => 72, 'h' => 72, 'crop' => 'y'));
                        $avatar_name = explode('/', str_replace('\\', '/', $user ['avatar']));
                        $user ['avatar_name'] = end($avatar_name);
                    }
                }

                $ret = $result;
            }
        }

        return $ret;
    }
    
    /**
     * Get all list
     * @return null|array
     */
    public function getAll(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;

        $select = $this->select()
                       ->from(self::TABLE_USER)
                       ->columns(array(
                           'userid',
                           'username'
                       ))
                       ->order('usergroupid asc')
                       ->order('joindate desc');
        
        $result = $this->fetchSelect($select);

        if($result){
           $ret = $result; 
        }

        return $ret;
    }
    
    /**
     * Get one
     * @param int $id
     * @return null|array
     */
    public function getOne($id = 0, $currentUserId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;

        $select = $this->getSQL();

        if ( $select instanceof \Zend\Db\Sql\Select && $id > 0 )
        {
            $where = new \Zend\Db\Sql\Where();

            $select
               ->from(array('u' => self::TABLE_USER))
               ->columns(array(
                    'userid',
                    'username',
                    'email',
                    'status',
               ))
               ->addJoinColumns('ur', array(
                    'rating_users' => $this->expr('COUNT(ur.id)'),
                    'rating' => $this->subQuery(
                        $this->select()
                        ->from(array('ur_sub2' => self::TABLE_USERS_RATING))
                        ->columns(array('rating' => $this->expr('IF(COUNT(id),rating,0)')))
                        ->where(array(
                            'ur_sub2.user_voted_id' => $currentUserId
                        ))
                        ->limit(1)
                    ),
                    'rating_mark' => $this->subQuery(
                        $this->select()
                        ->from(array('ur_sub3' => self::TABLE_USERS_RATING))
                        ->columns(array('rating_u' => $this->expr('IF(COUNT(id), SUM(rating) / COUNT(id), 0)')))
                        ->where(array(
                            'ur_sub3.user_id' => $this->expr('u.userid'),
                            $where->notEqualTo('rating', 0)
                        ))
                        ->limit(1)
                    )

                ))
               ->where(array(
                   'u.userid' => $id,
                   'u.usergroupid' => self::USERS_LEVEL_USER
                ))
               ->group('u.userid')
               ->limit(1);

            $result = $this->fetchRowSelect($select);

            if($result){
                $breadcrumbsArray = array();

                /*switch ($result ['type'])
                {
                    case self::TYPE_LEGAL:
                        $breadcrumbsArray [] = array(
                            'type' => self::TYPE_LEGAL,
                            'name' => 'Юр. лица',
                        );
                    break;
                    case self::TYPE_PHYSICAL:
                        $breadcrumbsArray [] = array(
                            'type' => self::TYPE_PHYSICAL,
                            'name' => 'Физ. лица',
                        );
                    break;
                }*/

                $breadcrumbsArray [] = array(
                    'id' => $result ['userid'],
                    'name' => $result ['username'],
                );

                $result ['breadcrumbs'] = $breadcrumbsArray;

                $result ['avatar_name'] = '';
                $result ['avatar_img'] = '';
                if ( !empty($result ['avatar']) )
                {
                    $result ['avatar_img'] = $this->getController()->easyUrl(array('module' => 'application', 'controller' => 'image', 'action' => 'user-avatar', 'id' => $result ['id'], 'w' => 72, 'h' => 72, 'crop' => 'y'));
                    $avatar_name = explode('/', str_replace('\\', '/', $result ['avatar']));
                    $result ['avatar_name'] = end($avatar_name);
                }

                $ret = $result;
            }
        }
        
        return $ret;
    }

    /**
     * Get one
     * @param int $id
     * @return null|string
     */
    public function getAvatar($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if ( $id > 0 )
        {
            $UsersFields = $this->load('UsersFields', 'admin');

            $ret = $UsersFields->get(array('user_id' => $id, 'name' => 'avatar'));
        }

        return $ret;
    }

    /**
     * Get User
     * @param int $id
     * @return array|null
     */
    public function getUsername($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if($id > 0){
            $select = $this->select()
                ->from(self::TABLE_USER)
                ->columns(array('username'))
                ->where(array('userid' => $id))
                ->limit(1);

            $result = $this->fetchOneSelect($select);

            if($result){
                $ret = $result;
            }
        }

        return $ret;
    }
    
    /**
     * Get User Name
     * @param int $id User ID
     * @return string|null
     */
    public function getName($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
            if($id > 0){
                $select = $this->select()
                               ->from(self::TABLE_USER)
                               ->columns(array('lastname', 'firstname', 'secondname'))
                               ->where(array('userid' => $id))
                               ->limit(1);
                
                $result = $this->fetchRowSelect($select);
                
                if($result){
                    $ret = $result ['lastname'] . ' ' . $result ['firstname'] . ' ' . $result ['secondname'];
                }
            }

        return $ret;
    }
    
    /**
     * Get User Name and Username
     * @param int $id User ID
     * @return array
     */
    public function getNameAndUsername($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = array(
            'readonly' => '',
            'username' => ''
        );
        
            if($id > 0){
                $select = $this->select()
                               ->from(self::TABLE_USER)
                               ->columns(array(
                                    'username'))
                               ->where(array('userid' => $id))
                               ->limit(1);
                
                $result = $this->fetchRowSelect($select);

                if ($result) {
                    $result['readonly'] = ' readonly="readonly"';
                    $ret = $result;
                }
            }
        
        return $ret;
    }
    
    
    
    /**
     * Get User Level
     * @param int $id User ID
     * @return string|null
     */
    public function getLevel($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
            if($id > 0){
                if(isset(self::$userLevel[$id])){
                    $ret = self::$userLevel[$id];
                }else{                    
                    $select = $this->select()
                                   ->from(self::TABLE_USER)
                                   ->columns(array('usergroupid'))
                                   ->where(array('userid' => $id))
                                   ->limit(1);

                    $result = $this->fetchOneSelect($select);

                    if($result){
                        self::$userLevel[$id] = $result;
                        $ret = $result;
                    }
                }
            }
        
        return $ret;
    }
    
    /**
     * Check level "user"
     * @param int $id
     * @return bool
     */
    public function checkUserLevel($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
            if($id > 0){
                $level = $this->getLevel($id);
                
                if($level === self::USERS_LEVEL_USER){
                    $ret = true;
                }
            }
        
        return $ret;
    }
    
    /**
     * Check level "admin"
     * @param int $id
     * @return bool
     */
    public function checkAdminLevel($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
            if($id > 0){
                $level = $this->getLevel($id);
                
                if($level === self::USERS_LEVEL_ADMIN){
                    $ret = true;
                }
            }
        
        return $ret;
    }
    
    /**
     * Edit
     * @param int $id
     * @param array $params
     * @param array $arrays
     * @return bool
     */
    public function edit($id = 0, $params = null, $arrays = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        
        if($id > 0 && $params !== null){ 
            
            if(isset($params['username']) && $this->load('User', 'profile')->checkLogin($params['username'], $id) == false){
                $set = array(
                    'username' => $params['username'],
                    'email' => $params['email'],
                    /*'star' => isset($params['star']) ? $params['star'] : 0,*/
                    'status' => isset($params['status']) ? $params['status'] : 'n'                    
                );


                /*$select = $this->select()
                    ->from(self::TABLE_USER)
                    ->columns(array('star' => 'star'))
                    ->where(array('userid' => $id));
                $oldStars = self::fetchOneSelect($select);*/
                if(
                    isset($params['password']) && 
                    isset($params['retry_password']) && 
                    !empty($params['password']) && 
                    $this->load('Validator')->validIdentical($params['password'], $params['retry_password']) === true
                  ){
                    $salt = $this->load('User', 'profile')->generateSalt();
                    $set['password'] = $this->expr('md5(?)', $params['password'].$salt);
                    $set['salt'] = $salt;
                }

                $update = $this->update(self::TABLE_USER)
                               ->set($set)
                               ->where(array('userid' => $id));

                $ret = $this->execute($update);

                /*if ($ret){
                    $stars = $set['star'] - $oldStars;
                    if ($stars > 0){
                        $this->load('SendEmail', 'admin')->refill($set['email'], $stars);
                    }
                }*/
            }

            if(isset($arrays['phone'])){
                $maskArray = isset($arrays['mask']) ? $arrays['mask'] : null;
                $this->load('UsersPhone', 'admin')->add($id, $arrays['phone'], $maskArray);
            } 
        }
        
        return (bool)$ret;
    }

    /**
     * Remove
     * @param int $id
     * @return bool
     */
    public function remove($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($id > 0){ 
            $delete = $this->delete(self::TABLE_USER)
                           ->where(array('userid' => $id));

            $ret = $this->execute($delete);
        }
        
        return (bool)$ret;
    }

    /**
     * get paginator
     * @param int $page
     * @return null|array
     */
    public function getPaginator($page = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $count = 0;
        $page = ($page > 0) ? $page : 1;

        $select = $this->select()
                       ->from(self::TABLE_USER)
                       ->columns(array(
                           'count' => $this->expr('count(*)')
                       ))                       
                       ->where(array(
                           'usergroupid' => self::USERS_LEVEL_USER
                       ));

        $count = (int)$this->fetchOneSelect($select);

        return $this->paginator($page, $count, self::USERS_PER_PAGE);
    }
    
    /**
     * Set status
     * @param int $id
     * @return bool
     */
    public function setStatus($userid = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($userid > 0){
            $select = $this->select()
                           ->from(self::TABLE_USER)
                           ->columns(array(
                               'status',
                               'usergroupid'
                           ))
                           ->where(array('userid' => $userid))
                           ->limit(1);
            
            $result = $this->fetchRowSelect($select);
            
            if(isset($result['status']) && isset($result['usergroupid']) && $result['usergroupid'] != self::USERS_LEVEL_ADMIN){
                $update = $this->update(self::TABLE_USER)
                               ->set(array(
                                   'status' => ($result['status'] == 'y' ? 'n' : 'y')
                               ))
                               ->where(array('userid' => $userid));
                
                $ret = $this->execute($update);
            }
        }
       
        return (bool)$ret;
    }
}