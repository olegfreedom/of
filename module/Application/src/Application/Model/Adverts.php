<?php
namespace Application\Model;

class Adverts extends \Application\Base\Model
{
    const POST_PER_PAGE = 20;
    const POST_RELATED = 3;
    const POST_COUNT_TOP = 5;

    private function getSQL()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $select = $this->select()
            ->from(array('a' => self::TABLE_ADVERTS))
            //->quantifier(self::SQL_DISTINCT)
            ->columns(array())
            ->join(
                array('ac' => self::TABLE_ADVERTS_CATEGORIES),
                'a.category = ac.id',
                array()
            )
            ->join(
                array('at' => self::TABLE_ADVERTS_TYPE),
                'a.type = at.id',
                array()
            )
            ->join(
                array('al' => self::TABLE_ADVERTS_LOCATION),
                'a.location = al.id',
                array()
            )
            ->join(
                array('ar' => self::TABLE_ADVERTS_LOCATION_REGIONS),
                'al.region_id = ar.id',
                array()
            )
            ->join(
                array('cur' => self::TABLE_ADVERTS_CURRENCY),
                'a.currency = cur.id',
                array()
            )
            ->join(
                array('ut' => self::TABLE_ADVERTS_UNIT_TYPES),
                'a.unit_type = ut.id',
                array()
            )
            ->join(
                array('u' => self::TABLE_USER),
                'a.user_id = u.id',
                array()
            )
            ->join(
                array('ag' => self::TABLE_ADVERTS_GALLERY),
                'ag.advert_id = a.id',
                array(),
                self::SQL_JOIN_LEFT
            )
            ->where(array(
                $this->where()
                    ->equalTo('a.status', 'y')
                    ->addPredicate(
                        $this->where()
                            ->equalTo('u.level', self::USERS_LEVEL_ADMIN)
                            ->orPredicate(
                                $this->where()
                                    ->equalTo('u.status', 'y')
                                    ->and
                                    ->greaterThan('a.lifetime', $this->load('Date', 'admin')->getDateTime())
                            )
                    )
            ));

        return $select;
    }

    /**
     * Set params
     * @param \Zend\Db\Sql\Select $select
     * @param array $params
     * @param bool $top
     * @return \Zend\Db\Sql\Select
     */
    private function setParams(\Zend\Db\Sql\Select $select, $params = null, $top = false)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if (isset($params['category']) && (int)$params['category'] > 0) {
            $catArray = $this->load('AdvertCategory', 'admin')->getSubIdArray($params['category']);
            $categories = is_array($catArray) ? $catArray : $params['category'];

            $select->where(array('ac.id' => $categories));
        }

        if (isset($params['type']) && (int)$params['type'] > 0) {
            $select->where(array('at.id' => $params['type']));
        }

        if (isset($params['user_id']) && (int)$params['user_id'] > 0) {
            $select->where(array('a.user_id' => $params['user_id']));
        }

        if ($top === true){
            $select->where(array('a.top' => 'y'))
                ->limit(self::POST_COUNT_TOP);
        } else {
            $select->where(array('a.top' => 'n'));
        }

        return $select;
    }

    /**
     * Get all list
     * @param array $params
     * @param bool $top
     * @return null|array
     */
    public function getList($params = null, $top = false)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->getSQL();

        if ($select instanceof \Zend\Db\Sql\Select) {
            $select->columns(array(
                    'id',
                    'name',
                    'price',
                    'user_id',
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
                    'created' => $this->expr('date_format(a.created, "%d %M %Y")')
                ))
                ->quantifier(self::SQL_DISTINCT)
                ->addJoinColumns('ac', array(
                    'category_id' => 'id',
                    'category' => 'name'
                ))
                ->addJoinColumns('at', array(
                    'type' => 'name',
                    'type_id' => 'id'
                ))
                ->addJoinColumns('al', array(
                    'location' => 'name'
                ))
                ->addJoinColumns('cur', array(
                    'currency' => 'name'
                ))
                ->addJoinColumns('u', array(
                    'user_name' => 'name',
                    'user_email' => 'username'
                ))
                ->order('a.timestamp desc');

            // set params
            $select = $this->setParams($select, $params, $top);

            //set search params
            $select = $this->setSearchParams($select, $params);

            if (isset($params['page']) && (int)$params['page'] > 0) {
                $select->limitPage($params['page'], self::POST_PER_PAGE);
            }

            $result = $this->fetchSelect($select);

            if ($result) {
                foreach ($result as &$item) {
                    $item['created'] = $this->load('Date', 'admin')->translateMonth($item['created']);

                    $breadcrumbsArray = $this->load('AdvertCategory', 'admin')->getBreadcrumbsArray($item['category_id']);
                    if (is_array($breadcrumbsArray)) {
                        $item['breadcrumbs'] = $breadcrumbsArray;
                    }
                }
                $ret = $result;
            }
        }
        //$this->debug($ret);

        return $ret;
    }

    /**
     * Set search params
     * @param \Zend\Db\Sql\Select $select
     * @param array $params
     * @return \Zend\Db\Sql\Select
     */
    private function setSearchParams(\Zend\Db\Sql\Select $select, $params = array())
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if (sizeof($params) > 0) {
            $columns = array(
                'location' => 'al.id',
                'region' => 'ar.id'
            );

            $where = array();
            $having = array();
            foreach ($params as $key => $val) {
                if (isset($columns[$key]) && $val !== 0 && $val != 'n') {
                    $where[$columns[$key]] = $val;
                }
            }
            if (isset($params['image']) && $params['image'] == 'y'){
                ///$having[] = 'img_id is not null';
                $where[] = $this->where()
                                ->isNotNull('ag.id');
                    
                    
            }
            if (isset($params['price_min']) && isset($params['price_max']) && $params['price_max'] > 0) {
                $where[] = $this->where()
                                ->between('a.price', $params['price_min'], $params['price_max']);
            }
            if (isset($params['search_text']) && !empty($params['search_text'])) {
                $searchExp = explode(' ', $params['search_text']);
                if (sizeof($searchExp) > 0) {
                    $searchWhere = $this->where();
                    foreach ($searchExp as $searchItem) {
                        $searchWhere->or
                                    ->like('a.name', '%' . $searchItem . '%')
                                    ->or
                                    ->like('a.description_full', '%' . $searchItem . '%');
                    }
                    $where[] = $searchWhere;
                }
            }
            $select->where($where)->having($having);
        }

        return $select;
    }

    /**
     * Get one
     * @param integer $id
     * @param integer $userId
     * @return array|null
     */
    public function getOne($id = 0, $userId = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if ((int)$id > 0) {
            $select = $this->getSQL();

            if ($select instanceof \Zend\Db\Sql\Select) {
                $select->columns(array(
                            'id',
                            'name',
                            'description_short',
                            'description_full',
                            'price',
                            'user_id',
                            'counter',
                            'favorite' => $this->subQuery(
                                   $this->select()
                                        ->from(array('sub_f' => self::TABLE_FAVORITES))
                                        ->columns(array('id'))
                                        ->where(array(
                                            'sub_f.advert_id' => $this->expr('a.id'),
                                            'sub_f.user_id' => $userId,
                                        ))
                                        ->limit(1)
                            ),
                            'avg_rating' => $this->subQuery(
                                   $this->select()
                                        ->from(array('t' => self::TABLE_TESTIMONIALS))
                                        ->columns(array('rating' => $this->expr('avg(t.rating)')))
                                        ->join(
                                            array('tta' => self::TABLE_TESTIMONIALS_TO_ADVERT),
                                            'tta.testimonial_id = t.id',
                                            array()
                                        )
                                        ->where(array(
                                            'tta.advert_id' => $this->expr('a.id'),
                                            't.active' => 'y',
                                            $this->where()->greaterThan('t.rating', 0)
                                        ))
                                        ->limit(1)
                            ),
                            'testimonial_count' => $this->subQuery(
                                   $this->select()
                                        ->from(array('t' => self::TABLE_TESTIMONIALS))
                                        ->columns(array('count' => $this->expr('count(t.id)')))
                                        ->join(
                                            array('tta' => self::TABLE_TESTIMONIALS_TO_ADVERT),
                                            'tta.testimonial_id = t.id',
                                            array()
                                        )
                                        ->where(array(
                                            'tta.advert_id' => $this->expr('a.id'),
                                            't.active' => 'y'
                                        ))
                                        ->limit(1)
                            ),
                            'avg_author_rating' => $this->subQuery(
                                   $this->select()
                                        ->from(array('t' => self::TABLE_TESTIMONIALS))
                                        ->columns(array('rating' => $this->expr('avg(t.rating)')))
                                        ->join(
                                            array('tta' => self::TABLE_TESTIMONIALS_TO_ADVERT),
                                            'tta.testimonial_id = t.id',
                                            array()
                                        )
                                        ->join(
                                            array('adv' => self::TABLE_ADVERTS),
                                            'adv.id = tta.advert_id',
                                            array()
                                        )
                                        ->where(array(
                                            't.active' => 'y',
                                            'adv.user_id' => $this->expr('a.user_id'),
                                             $this->where()->greaterThan('t.rating', 0)
                                        ))
                                        ->limit(1)
                            ),
                            'created' => $this->expr('date_format(a.created, "%d %M %Y")')
                        ))
                        ->addJoinColumns('ac', array(
                            'category_id' => 'id',
                            'category' => 'name'
                        ))
                        ->addJoinColumns('at', array(
                            'type' => 'name'
                        ))
                        ->addJoinColumns('al', array(
                            'location' => 'name'
                        ))
                        ->addJoinColumns('cur', array(
                            'currency' => 'name'
                        ))
                        ->addJoinColumns('ut', array(
                            'unit_type' => 'shortname'
                        ))
                        ->addJoinColumns('u', array(
                            'user_name' => 'name',
                            'user_email' => 'username'
                        ))
                        ->where(array(
                            'a.id' => $id
                        ))
                        ->limit(1);

                $result = $this->fetchRowSelect($select);

                if ($result) {
                    $result['created'] = $this->load('Date', 'admin')->translateMonth($result['created']);

                    $breadcrumbsArray = $this->load('AdvertCategory', 'admin')->getBreadcrumbsArray($result['category_id']);
                    if (is_array($breadcrumbsArray)) {
                        $result['breadcrumbs'] = $breadcrumbsArray;
                    }

                    $ret = $result;
                }
            }
        }
        return $ret;
    }

    /**
     * Get related list
     * @param integer $id
     * @return null|array
     */
    public function getRelated($id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if ((int)$id > 0) {
            $select = $this->getSQL();

            if ($select instanceof \Zend\Db\Sql\Select) {
                $select->columns(array(
                    'id',
                    'name',
                    'price',
                    'user_id',
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
                    'created' => $this->expr('date_format(a.created, "%d %M %Y")')
                ))
                    ->quantifier(self::SQL_DISTINCT)
                    ->addJoinColumns('ac', array(
                        'category_id' => 'id',
                        'category' => 'name'
                    ))
                    ->addJoinColumns('at', array(
                        'type' => 'name'
                    ))
                    ->addJoinColumns('al', array(
                        'location' => 'name'
                    ))
                    ->addJoinColumns('cur', array(
                        'currency' => 'name'
                    ))
                    ->addJoinColumns('u', array(
                        'user_name' => 'name',
                        'user_email' => 'username'
                    ))
                    ->where(array(
                        'a.category' => $this->subQuery(
                                $this->select()
                                    ->from(self::TABLE_ADVERTS)
                                    ->columns(array('category'))
                                    ->where(array('id' => $id))
                                    ->limit(1)
                            ),
                        $this->where()->notEqualTo('a.id', $id)
                    ))
                    ->order('a.timestamp desc');

                $result = $this->fetchSelect($select);

                if ($result) {
                    $result = $this->getRandItems($result);

                    foreach ($result as &$item) {
                        $item['created'] = $this->load('Date', 'admin')->translateMonth($item['created']);

                        $breadcrumbsArray = $this->load('AdvertCategory', 'admin')->getBreadcrumbsArray($item['category_id']);
                        if (is_array($breadcrumbsArray)) {
                            $item['breadcrumbs'] = $breadcrumbsArray;
                        }
                    }

                    $ret = $result;
                }
            }
        }

        return $ret;
    }

    /**
     * Get random items
     * @param array $array
     * @return null|array
     */
    private function getRandItems($array = null)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if (is_array($array)) {
            shuffle($array);
            $ret = array();

            for ($i = 0; $i < self::POST_RELATED; $i++) {
                if (isset($array[$i])) {
                    $ret[] = $array[$i];
                }
            }
        }

        return $ret;
    }

    /**
     * Ger max prise
     * @return array|bool
     */
    public function getMaxPrice()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        $select = $this->select()
                       ->from(self::TABLE_ADVERTS)
                       ->columns(array(
                           'max' => $this->expr('max(price)')
                       ))
                       ->where(array(
                           'status' => 'y'
                       ));

        $result = $this->fetchOneSelect($select);

        if ($result) {
            $ret = $result;
        }
        return $ret;
    }
    
    
    /**
     * 
     * Get advert comments
     * 
     */
    public function getAdvertComments($id = 0) {
        
        $select = $this->select()
               ->from(array('t' => self::TABLE_TESTIMONIALS))
               ->columns(array(
                    'id',
                    'name',
                    'email',
                    'message',
                    'rating',
                    'type',
                    'active',
                    'date' => $this->expr('date_format(t.timestamp, "%d.%m.%Y %H:%i")')
                ))
                ->order('t.timestamp desc')
                ->join(
                    array('tta' => self::TABLE_TESTIMONIALS_TO_ADVERT),
                    'tta.testimonial_id = t.id',
                    array()
                  )
                ->join(
                    array('ta' => self::TABLE_ADVERTS),
                    'ta.id = tta.advert_id',
                    array()
                  )
                ->where(array('t.active' => 'y',
                               'ta.id' => $id
                    ));
        
        $ret = $this->fetchSelect($select);
        
        return $ret;
    }

    /**
     * get paginator
     * @param array $params
     * @return null|array
     */
    public function getPaginator($params = array())
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $count = 0;
        $page = ((int)$params['page'] > 0) ? (int)$params['page'] : 1;

        $select = $this->getSQL();

        if ($select instanceof \Zend\Db\Sql\Select) {

            $select->columns(array(
                'count' => $this->expr('count('.self::SQL_DISTINCT.' a.id)')
            ));

            // set params
            $select = $this->setParams($select, $params);

            //set search params
            $select = $this->setSearchParams($select, $params);

            $count = (int)$this->fetchOneSelect($select);

        }
        return $this->paginator($page, $count, self::POST_PER_PAGE);
    }
    
    /**
     * Get adverts count
     * @param array $params
     * @param bool $top
     * @return null|array
     */
    public function getListCount($params = null, $top = false)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = 0;

        $select = $this->getSQL();

        if ($select instanceof \Zend\Db\Sql\Select) {
            $select->columns(array(
                'count' => $this->expr('count('.self::SQL_DISTINCT.' a.id)')
            ));

            // set params
            $select = $this->setParams($select, $params);

            //set search params
            $select = $this->setSearchParams($select, $params);

            if ($top === true){
                $select->where(array('a.top' => 'y'))
                       ->limit(self::POST_COUNT_TOP);
            }
            $result = (int)$this->fetchOneSelect($select);

            if ($result > 0) {
                $ret = $result;
            }
        }
        return $ret;
    }
}