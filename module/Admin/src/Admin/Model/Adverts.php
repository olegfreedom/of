<?php
namespace Admin\Model;

class Adverts extends \Application\Base\Model
{
    const POST_PER_PAGE = 20;

    // Товары, Услуги, Технологии Инвестиции
    const TYPE_ALL = 0;
    const TYPE_SERVICE_BUY = 1;
    const TYPE_SERVICE_SELL = 2;
    const TYPE_PRODUCT_BUY = 3;
    const TYPE_PRODUCT_SELL = 4;
    const TYPE_TECHNOLOGIES_BUY = 5;
    const TYPE_TECHNOLOGIES_SELL = 6;
    const TYPE_INVESTMENTS_BUY = 7;
    const TYPE_INVESTMENTS_SELL = 8;

    private $selectFieldsAr = array(
        'id',
        'category',
        'type',
        'location',
        'name',
        'description_short',
        'description_full',
        'contact_name',
        'price',
        'currency',
        'unit_type',
        'status',
        'user_id'
    );

    
    private function getSQL(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $select = $this->select()
            ->from(array('a' => self::TABLE_ADVERTS))
            ->columns(array())
            ->join(
                array('ac' => self::TABLE_ADVERTS_CATEGORIES),
                'ac.id = a.category',
                array()
            )
            ->join(
                array('ar' => self::TABLE_ADVERTS_RATING),
                'ar.advert_id = a.id',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('at' => self::TABLE_ADVERTS_TYPE),
                'at.id = a.type',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('ut' => self::TABLE_ADVERTS_UNIT_TYPES),
                'ut.id = a.unit_type',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('acu' => self::TABLE_ADVERTS_CURRENCY),
                'acu.id = a.currency',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                 array('u' => self::TABLE_USER),
                 'u.userid = a.user_id',
                 array(),
                 self::SQL_JOIN_LEFT
            )
            ->join(
                array('ufl' => self::TABLE_USERS_FIELDS),
                $this->expr('ufl.user_id = u.userid AND ufl.name = ?', array('username')),
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('ufz' => self::TABLE_USERS_FIELDS),
                $this->expr('ufz.user_id = u.userid AND ufz.name = ?', array('zip')),
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                array('lz' => self::TABLE_LOCATION_ZIP),
                'lz.name = ufz.value',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->join(
                 array('lc' => self::TABLE_LOCATION_CITIES),
                 'lc.id = lz.city_id',
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
        ;

        return $select;
    }
    
    /**
     * Set params
     * @param \Zend\Db\Sql\Select $select
     * @param array $params
     * @return \Zend\Db\Sql\Select
     */
    private function setParams(\Zend\Db\Sql\Select $select, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if(isset($params['category']) && (int)$params['category'] > 0){
            $catArray = $this->load('AdvertCategory', 'admin')->getSubIdArray($params['category']);
            $categories = is_array($catArray) ? $catArray : $params['category'];

            $select->where(array('ac.id' => $categories));
        }

        if(isset($params['type']) && (int)$params['type'] > 0){
            $select->where(array('at.id' => $params['type']));
        }

        if(isset($params['user_id']) && (int)$params['user_id'] > 0){
            $select->where(array('a.user_id' => $params['user_id']));
        } 
        
        return $select;
    }

    /**
     * Get all list
     * @param array $params
     * @param int $userId
     * @param bool $favorite
     * @return null|array
     */
    public function getList($params = null, $userId = 0, $favorite = false){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $ret = null;

        if ( isset($params ['type']) && $params ['type'] === self::TYPE_ALL )
        {
            $neededTypesAr = array(
                self::TYPE_PRODUCT_BUY,
                self::TYPE_PRODUCT_SELL,
                self::TYPE_SERVICE_BUY,
                self::TYPE_SERVICE_SELL,
                self::TYPE_TECHNOLOGIES_BUY,
                self::TYPE_TECHNOLOGIES_SELL,
                self::TYPE_INVESTMENTS_BUY,
                self::TYPE_INVESTMENTS_SELL,
            );
            $advertsList = array();

            foreach ( $neededTypesAr as $type )
            {
                $newParams = array_merge($params, array('type' => $type));
                $advertsList [$type] = $this->getList($newParams);
            }

            $ret = $advertsList;
        }
        else
        {
            $ret = null;

            $select = $this->getSQL();

            if($select instanceof \Zend\Db\Sql\Select){
                $where = new \Zend\Db\Sql\Where();

                $select->columns(array(
                           'id',
                           'name',
                           'description_short',
                           'price',
                           'status',
                           'user_id',
                           'contact_name',
                           'counter',
                           'top',
                           'location' => $this->subQuery(
                                   $this->select()
                                        ->from(array('u_old' => self::TABLE_USER_OLD))
                                        ->columns(array('zip'))
                                        ->where(array(
                                           'id' => $this->expr('a.id')
                                       ))
                                        ->limit(1)
                                       ),
                           'img_id' => $this->subQuery(
                                        $this->select()
                                            ->from(array('ra_sub' => self::TABLE_ADVERTS_GALLERY))
                                            ->columns(array('id'))
                                            ->where(array(
                                                'advert_id' => $this->expr('a.id')
                                            ))
                                            ->order('ra_sub.id asc')
                                            ->limit(1)
                                    ),
                           /*
                           'date_id' => $this->subQuery(
                               $this->select()
                                   ->from(array('ad_sub' => self::TABLE_ADVERTS_DATES))
                                   ->columns(array('id'))
                                   ->where(array(
                                       'advert_id' => $this->expr('a.id')
                                   ))
                                   ->order('ad_sub.date_from asc')
                                   ->limit(1)
                           ),
                           */
                           'rating_mark' => $this->subQuery(
                               $this->select()
                                    ->from(array('ar_sub' => self::TABLE_ADVERTS_RATING))
                                    ->columns(array('rating_a' => $this->expr('IF(COUNT(id), ROUND(SUM(rating) / COUNT(id)), 0)')))
                                    ->where(array(
                                        'ar_sub.advert_id' => $this->expr('a.id'),
                                        $where->notEqualTo('rating', 0)
                                    ))
                                    ->limit(1)
                           ),

                           'lifetime' => $this->expr('unix_timestamp(a.lifetime)'),
                           'created' => $this->expr('unix_timestamp(a.created)')
                       ))
                       ->addJoinColumns('ac', array(
                            'category_id' => 'id',
                            'category' => 'name'
                       ))
                       ->addJoinColumns('at', array(
                            'type_id' => 'id',
                            'type' => 'name'
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
                       ->addJoinColumns('cur', array(
                            'currency_id' => 'id',
                            'currency' => 'name'
                       ))
                       ->addJoinColumns('acu', array(
                            'currency_id' => 'id',
                            'currency' => 'name',
                       ))
                       ->addJoinColumns('ut', array(
                            'unit_type_id' => 'id',
                            'unit_type' => 'shortname',
                       ))
                        ->addJoinColumns('ufl', array(
                            'username' => 'value',
                        ))
                       ->addJoinColumns('u', array(
                            'user_usergroupid' => 'usergroupid',
                            'user_status' => 'status',
                         /*   'user_level' => 'level'
                        */
                       ))
                       ->order('a.timestamp desc');

                $select->group('a.id');

                if($favorite == true && $userId > 0){
                    $select->join(
                        array('f' => self::TABLE_FAVORITES),
                        'f.advert_id = a.id',
                        array(
                            'favorite_id' => 'id'
                        )
                    )
                        ->where(array(
                            'f.user_id' => $userId,
                        ))
                        ->reset('order')
                        ->order('f.timestamp desc');
                }

                // set params
                $select = $this->setParams($select, $params);

                if(isset($params['page']) && (int)$params['page'] > 0){
                    $select->limitPage($params['page'], self::POST_PER_PAGE);
                }

                $result = $this->fetchSelect($select);

                if($result){
                    foreach($result as &$item){
/*
                        $item['days_left'] = $this->load('Date', 'admin')->daysLeft($item['lifetime']);
                        $item['days_text'] = $this->load('Date', 'admin')->daysText($item['days_left']);

                        $item['active_status'] = (bool)($item['status'] == 'y' && ($item['lifetime'] > time() || $item['user_level'] == self::USERS_LEVEL_ADMIN));
                        $item['is_admin'] = (bool)($item['user_level'] == self::USERS_LEVEL_ADMIN);
*/                      $item['location'] = $this->load('Location', 'admin')->getLocationByZip($item['location']);
                        $item['is_admin'] = (bool)($item ['user_usergroupid'] == \Profile\Model\Vbulletin::ADMIN_USERGROUP);

                        $breadcrumbsArray = $this->load('AdvertCategory', 'admin')->getBreadcrumbsArray($item['category_id']);
                        if(is_array($breadcrumbsArray)){
                            $item['breadcrumbs'] = $breadcrumbsArray;
                        }
                    }

                    $ret = array(
                        'data' => $result,
                        'paginator' => $this->getPaginator($params)
                    );
                }
            }
        }

        return $ret;
    }
    
    /**
     * Get one
     * @param int $id
     * @param int $userId
     * @return null|array
     */
    public function getOne($id = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        $select = $this->getSQL();
        
        if($select instanceof \Zend\Db\Sql\Select){
            if((int)$id > 0){

                $where = new \Zend\Db\Sql\Where();

                $select->columns(array(
                           'id',
                           'category',
                           'type',
                           'location',
                           'name',
                           'description_short',
                           'description_full',
                           'contact_name',
                           'price',
                           'price_fond',
                           'discount',
                           'status',
                           'user_id',
                           'lifetime' => $this->expr('unix_timestamp(a.lifetime)'),
                           'created' => $this->expr('unix_timestamp(a.created)')
                       ))
                       ->addJoinColumns('u', array(
                            'user_level' => 'usergroupid',
                            'user_username' => 'username',
                       ))
                        ->addJoinColumns('lc', array(
                            'city_id' => 'id',
                            'city' => 'name'
                        ))
                        ->addJoinColumns('la', array(
                            'area_id' => 'id',
                            'area' => 'name'
                        ))
                        ->addJoinColumns('ar', array(
                            'rating_users' => $this->expr('COUNT(ar.id)'),
                            'rating' => $this->subQuery(
                                $this->select()
                                    ->from(array('ar_sub2' => self::TABLE_ADVERTS_RATING))
                                    ->columns(array('rating'))
                                    ->where(array(
                                        'ar_sub2.advert_id' => $this->expr('a.id'),
                                        'ar_sub2.user_id' => $userId
                                    ))
                                    ->limit(1)
                            ),
                            'rating_mark' => $this->subQuery(
                                $this->select()
                                    ->from(array('ar_sub2' => self::TABLE_ADVERTS_RATING))
                                    ->columns(array('rating_u' => $this->expr('SUM(rating) / COUNT(id)')))
                                    ->where(array(
                                        'ar_sub2.advert_id' => $this->expr('a.id'),
                                        $where->notEqualTo('rating', 0)
                                    ))
                                    ->limit(1)
                            )

                        ))
                        ->addJoinColumns('lr', array(
                            'region_id' => 'id',
                            'region' => 'name'
                        ))
                        ->addJoinColumns('ut', array(
                            'unit_type_id' => 'id',
                            'unit_type' => 'shortname',
                        ))
                        ->addJoinColumns('acu', array(
                            'currency_id' => 'id',
                            'currency' => 'name',
                        ))
                       ->where(array('a.id' => $id))
                       ->group('a.id')
                       ->limit(1);


                $result = $this->fetchRowSelect($select);

                if($result){
                    $breadcrumbsArray = $this->load('AdvertCategory', 'admin')->getBreadcrumbsArray($result['category']);
                    if(is_array($breadcrumbsArray)){
                        $result ['breadcrumbs'] = $breadcrumbsArray;
                    }

                    $result ['is_voted'] = ($result ['user_id'] == $userId) || !is_null($result ['rating']);

                    $ret = $result;
                }
            }
        }
        
        return $ret;
    }
       
    /**
     * Add
     * @param array $params
     * @param array $arrays
     * @return bool
     */
    public function add($params = null, $arrays = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        if($params !== null){
            $params['timestamp'] = microtime(true);
            $params['created'] = $this->load('Date', 'admin')->getDateTime();
            if ($params['status'] == 'y'){
                $params['lifetime'] = date(self::MYSQL_DATE_FORMAT, $this->load('Date', 'admin')->setInterval());
            }

            $insert = $this->insert(self::TABLE_ADVERTS)
                           ->values($params);

            $ret = $this->execute($insert);
            $id = $this->insertId();

            if ($id > 0) {
                if (isset($params['name']) && isset($params['user_id'])) {
                    $this->load('SendEmail', 'admin')->addAdvert($params['name'], $params['user_id']);
                    if (isset($params['lifetime'])) {
                        $this->load('SendEmail', 'admin')->prolongTime($params['name'], $params['user_id']);
                    }
                }

                if (isset($arrays['phone'])) {
                    $maskArray = isset($arrays['mask']) ? $arrays['mask'] : null;
                    $this->load('AdvertPhone', 'admin')->add($id, $arrays['phone'], $maskArray);
                }
                if (isset($arrays['gallery'])) {
                    $this->load('AdvertGallery', 'admin')->add($id, $arrays['gallery']);
                }

                if (isset($arrays['options']) && isset($params['category'])) {
                    $this->load('Options', 'admin')->addAdvertOptions($id , $arrays['options'], $params['category']);
                }

                if ( isset($arrays ['date_from']) && isset($arrays ['date_to']) )
                {
                    $Validator = new \Zend\Validator\Date(array('format' => 'd.m.Y'));

                    foreach ( $arrays ['date_from'] as $key => $date_from )
                    {
                        $date_to = $arrays ['date_to'] [$key];

                        if ( $Validator->isValid($date_from) && $Validator->isValid($date_to) )
                        {
                            $date_from_ts = strtotime($date_from);
                            $date_to_ts = strtotime($date_to);

                            if ( !empty($date_from_ts) && !empty($date_to_ts) )
                            {
                                if ( $date_from_ts > $date_to_ts )
                                {
                                    // Меняем местами
                                    $this->load('AdvertDate', 'admin')->add(array(
                                        'advert_id' => $id,
                                        'date_from' => date('Y-m-d', $date_to_ts),
                                        'date_to' => date('Y-m-d', $date_from_ts),
                                    ));
                                }
                                else
                                {
                                    $this->load('AdvertDate', 'admin')->add(array(
                                        'advert_id' => $id,
                                        'date_from' => date('Y-m-d', $date_from_ts),
                                        'date_to' => date('Y-m-d', $date_to_ts),
                                    ));
                                }
                            }
                        }

                    }
                }
            } 
        }
        
        return (bool)$ret;
    }
    
    /**
     * Edit
     * @param int $id
     * @param array $params
     * @param array $arrays
     * @param int $userId
     * @return bool
     */
    public function edit($id = 0, $params = null, $arrays = null, $userId = 0){
        $ret = false;

        if((int)$id > 0 && $params !== null){
            $advert = $this->getOne($id);
            $update = $this->update(self::TABLE_ADVERTS)
                           ->set($params)
                           ->where(array('id' => $id));

            if($userId > 0){
                $update->where(array('user_id' => $userId));
            }

            $ret = $this->execute($update);

            if(isset($params['status']) && isset($advert['status']) && isset($advert['lifetime']) &&  isset($advert['user_level']) && $userId == 0){
                if((int)$advert['lifetime'] == 0 && $advert['status'] == 'n' && $params['status'] == 'y' && $advert['user_level'] == self::USERS_LEVEL_USER){
                    $this->prolong($id);
                }
            }

            if(isset($arrays['gallery'])){
                $this->load('AdvertGallery', 'admin')->remove($id);
                $this->load('AdvertGallery', 'admin')->add($id, $arrays['gallery']);
            }

            if (isset($arrays['phone'])) {
                $maskArray = isset($arrays['mask']) ? $arrays['mask'] : null;
                $this->load('AdvertPhone', 'admin')->add($id, $arrays['phone'], $maskArray);
            }

            if ( isset($arrays ['date_from']) && isset($arrays ['date_to']) )
            {
                // Очищаем предыдущие даты
                $this->load('AdvertDate', 'admin')->remove(array(
                    'advert_id' => $id,
                ));
                if ( !empty($arrays ['date_from']) && !empty($arrays ['date_to']) )
                {
                    $Validator = new \Zend\Validator\Date(array('format' => 'd.m.Y'));

                    foreach ( $arrays ['date_from'] as $key => $date_from )
                    {
                        $date_to = $arrays ['date_to'] [$key];

                        if ( $Validator->isValid($date_from) && $Validator->isValid($date_to) )
                        {
                            $date_from_ts = strtotime($date_from);
                            $date_to_ts = strtotime($date_to);

                            if ( !empty($date_from_ts) && !empty($date_to_ts) )
                            {
                                if ( $date_from_ts > $date_to_ts )
                                {
                                    // Меняем местами
                                    $this->load('AdvertDate', 'admin')->add(array(
                                        'advert_id' => $id,
                                        'date_from' => date('Y-m-d', $date_to_ts),
                                        'date_to' => date('Y-m-d', $date_from_ts),
                                    ));
                                }
                                else
                                {
                                    $this->load('AdvertDate', 'admin')->add(array(
                                        'advert_id' => $id,
                                        'date_from' => date('Y-m-d', $date_from_ts),
                                        'date_to' => date('Y-m-d', $date_to_ts),
                                    ));
                                }
                            }
                        }

                    }
                }
            }
        }
        return (bool)$ret;
    }
    
    /**
     * Remove
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function remove($id = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;

        if((int)$id > 0){ 
            $advert = $this->getOne($id);

            $delete = $this->delete(self::TABLE_ADVERTS)
                           ->where(array('id' => $id));
            
            if((int)$userId > 0){
                $delete->where(array('user_id' => $userId));
            }

            $this->load('AdvertGallery', 'admin')->remove($id);
            $ret = $this->execute($delete);

            if($ret){
                if (isset($advert['name']) && isset($advert['user_id'])) {
                    $this->load('SendEmail', 'admin')->deleteAdvert($advert['name'], $advert['user_id']);
                }
            }
        }
        
        return (bool)$ret;
    }
    
    /**
     * Set status
     * @param int $id
     * @param array $params
     * @return bool
     */
    public function setStatus($id = 0, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($id > 0){            
            $select = $this->select()
                           ->from(array('a' => self::TABLE_ADVERTS))
                           ->columns(array(
                               'name',
                               'status',
                               'user_id',
                               'lifetime' => $this->expr('unix_timestamp(a.lifetime)')
                           ))
                           ->join(
                                array('u' => self::TABLE_USER),
                                'u.userid = a.user_id',
                                array(
                                    'user_level' => 'usergroupid'
                                )
                           )
                           ->where(array('a.id' => $id))
                           ->limit(1);
            
            $result = $this->fetchRowSelect($select);
            
            if(isset($result['status']) && isset($result['lifetime']) && isset($result['user_level'])){                
                $update = $this->update(self::TABLE_ADVERTS)
                               ->set(array(
                                   'status' => ($result['status'] == 'y' ? 'n' : 'y')
                               ))
                               ->where(array('id' => $id));
                
                $ret = $this->execute($update);

                if ($ret && isset($result['name']) && isset($result['user_id'])){
                    $this->load('SendEmail', 'admin')->activationAdvert($id, $result['name'], $result['user_id'], $result['status'], $params);
                }

                $result['lifetime'] = (int)$result['lifetime'];
                if($result['lifetime'] == 0 && $result['status'] == 'n' && $result['user_level'] == self::USERS_LEVEL_USER){
                    $this->prolong($id);
                }
            }
        }
       
        return (bool)$ret;
    }

    /**
     * lift advert
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function lift($id = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0){
            $update = $this->update(self::TABLE_ADVERTS)
                ->set(array(
                    'timestamp' => microtime(true)
                ))
                ->where(array('id' => $id));

            if($userId > 0){
                $update->where(array('user_id' => $userId));

                $ret = (bool)$this->execute($update);

            }else{
                $ret = (bool)$this->execute($update);
            }

        }

        return $ret;
    }

    /**
     * Put advert in top
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function top($id = 0, $userId = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($userId > 0 && $id > 0) {

            $pay = $this->load('User', 'profile')->payment($userId, self::STARS_TOP);

            if ($pay) {
                $update = $this->update(self::TABLE_ADVERTS)
                    ->set(array(
                        'top' => 'y'
                    ))
                    ->where(array('id' => $id));

                $ret = (bool)$this->execute($update);

            }
        }

        return $ret;
    }
    
    /**
     * Prolong advert
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function prolong($id = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($id > 0){ 
            $select = $this->select()
                           ->from(self::TABLE_ADVERTS)
                           ->columns(array(
                               'name',
                               'user_id',
                               'lifetime' => $this->expr('unix_timestamp(lifetime)')
                           ))
                           ->where(array('id' => $id))
                           ->limit(1);
            
            $advert = $this->fetchRowSelect($select);
            
            $newTime = $this->load('Date', 'admin')->setInterval(isset($advert['lifetime']) ? $advert['lifetime'] : 0);
            
            $update = $this->update(self::TABLE_ADVERTS)
                           ->set(array(
                               'lifetime' => date(self::MYSQL_DATE_FORMAT, $newTime)
                           ))
                           ->where(array('id' => $id));

            if ($userId > 0) {
                $update->where(array('user_id' => $userId));

                $ret = (bool)$this->execute($update);

            } else {
                $ret = (bool)$this->execute($update);
            }

            if ($ret && isset($advert['name']) && isset($advert['user_id'])) {
                $this->load('SendEmail', 'admin')->prolongTime($advert['name'], $advert['user_id']);
            }
        }
        
        return $ret;
    }
    
    /**
     * get paginator
     * @param array $params
     * @return null|array
     */
    public function getPaginator($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $count = 0;
        $page = 1;
        
        $select = $this->getSQL();
        
        if($select instanceof \Zend\Db\Sql\Select){
            $select->columns(array(
                       'count' => $this->expr('count(*)')
                   ));

            // set params
            $select = $this->setParams($select, $params);

            if(isset($params['page']) && (int)$params['page'] > 0){
                $page = $params['page'];
            }
            
            $count = (int)$this->fetchOneSelect($select);
        }

        return $this->paginator($page, $count, self::POST_PER_PAGE);
    }


    public function getValueFromUserFields($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0){
            $select = $this->select()
                ->from(array('ufl' => self::TABLE_USERS_FIELDS))
                ->columns(array(
                    'name',
                    'value',
                    'visibility',
                    'edited'
                ))
                ->where(array('user_id' => $id));

            $result = $this->fetchSelect($select);


            foreach($result as $value){
                $res[$value['name']] = $value['value'];
            }
        }
        if(isset($result)){
            $ret = $res;
        }

        return  $ret;
    }
    
}
