<?php

namespace Admin\Model;

class Options extends \Application\Base\Model {

    const OPTIONS_PER_PAGE = 20;
    
    private function getSQL(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $select = $this->select()
                       ->columns(array())
                       ->from(array('o' => self::TABLE_OPTIONS))
                       ->join(
                           array('c2o' => self::TABLE_CATEGORY_TO_OPTION),
                           'o.id = c2o.option_id',
                           array()
                       )
                       ->join(
                           array('c' => self::TABLE_ADVERTS_CATEGORIES),
                           'c.id = c2o.cat_id',
                           array(
                               'category_name' => 'name',
                               'category_id' => 'id'
                           )
                       );
        
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
            $catArray = $this->load('AdvertCategory', 'admin')->getParentsArray($params['category'], true);
            $categories = is_array($catArray) ? $catArray : $params['category'];
            
            $select->where(array('c2o.cat_id' => $categories));
        }
        
        return $select;
    }
    

    /**
     * Get list of options
     * @param array $params
     * @param bool $orderByTypeAndDecode 
     * @return array|bool
     */
    public function getList($params = null, $orderByTypeAndDecode = false){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;        
        
        $select = $this->getSQL();
        
        if($select instanceof \Zend\Db\Sql\Select){
            $columns = array(
                           'id',
                           'type',
                           'name',
                           'value'
                       );
            
            if (isset($params['advert_id']) && (int)$params['advert_id'] > 0){
                $advert = true;
                $columns['advert_value'] =  $this->subQuery($this->select()
                                                 ->from(array('ao' => self::TABLE_ADVERTS_OPTIONS))
                                                 ->columns(array('value'))
                                                 ->where(array(
                                                         'ao.option_id' => $this->expr('c2o.id'),
                                                         'ao.advert_id' => $params['advert_id']
                                                     ))
                                                 ->limit(1)
                                            );
            }
            
            
            $select->columns($columns);
            
            // set params
            $select = $this->setParams($select, $params);
            
            if ($orderByTypeAndDecode === true){
                $select->order('o.type asc');
            }

            $select->order('o.id desc');

            if(isset($params['page']) && (int)$params['page'] > 0){
                $select->limitPage($params['page'], self::OPTIONS_PER_PAGE);
            }
            
            $result = $this->fetchSelect($select);

            if ($result){
                foreach ($result as &$item){
                    if($orderByTypeAndDecode === true){ // TODO: Нужно чтоб не декодировалось value при выводе списка опций в админке     
                        if ($item['type'] == 'select' || $item['type'] == 'radio' || $item['type'] == 'multi'){
                            $item['value'] = $this->jsonDecode($item['value']);

                            if (isset($advert) && !is_null($item['advert_value'])){ // TODO: если делается выборка значения по умолчанию для объявления
                                $item['advert_value'] = $this->jsonDecode($item['advert_value']);

                                foreach($item['value'] as &$val){
                                    if($item['advert_value'] !== null){ // TODO: задаем значение если оно не null               
                                        if(is_array($item['advert_value'])){ // TODO: если мультиселект
                                            $val['selected'] = in_array($val['value'], $item['advert_value']) ? 'y' : 'n';
                                        }else{
                                            $val['selected'] = ($val['value'] == $item['advert_value']) ? 'y' : 'n';
                                        }
                                    }else{

                                        $val['selected'] = 'n';
                                    }
                                }
                            }
                        }else{
                            if (isset($advert) && $item['advert_value'] !== null){
                                $item['value'] = $item['advert_value'];
                            }
                        }
                    }else{                    
                        $breadcrumbsArray = $this->load('AdvertCategory', 'admin')->getBreadcrumbsArray($item['category_id']);
                        if(is_array($breadcrumbsArray)){
                            $item['breadcrumbs'] = $breadcrumbsArray;
                        }
                    }
                }

                $ret = $result;
            }

        }
        return $ret;
    }

    /**
     * Get option by ID
     * @param $id
     * @return array|bool
     */
    public function getOne($id = array())
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($id > 0){
            $select = $this->select()
                        ->from(array('o' => self::TABLE_OPTIONS))
                        ->columns(array(
                                'id',
                                'type',
                                'name',
                                'value',
                                'category_id' => $this->subQuery(
                                    $this->select()
                                         ->from(array('o2c' => self::TABLE_CATEGORY_TO_OPTION))
                                         ->columns(array('cat_id'))
                                         ->where(array('o2c.option_id' => $id))
                                         ->limit(1)
                                )
                        ))
                        ->where(array('o.id' => $id));
            $result = $this->fetchRowSelect($select);

            if ($result['type'] == 'select' || $result['type'] == 'radio' || $result['type'] == 'multi'){
                $result['value'] = $this->jsonDecode($result['value'], true);
            }

            if ($result){
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * Add option
     * @param array $params
     * @return bool
     */
    public function add($params = null)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($params !== null) {            
            $insert = $this->insert(self::TABLE_OPTIONS)
                           ->values(array(
                               'type' => $params['type'],
                               'name' => $params['name'],
                               'value' => $this->setValue($params)
                           ));
            
            $result = $this->execute($insert);
            $id = $this->insertId($result);
            
            if ($result && (int)$id > 0 && isset($params['category_id'])){
                $ret = $this->addCategoryToOptions($params['category_id'], $id);
            }
        }
        return $ret;
    }

    /**
     * Edit option
     * @param array $params
     * @param int $id
     * @return bool
     */
    public function edit($params = null, $id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        if ($params !== null && (int)$id > 0 && isset($params['type']) && isset($params['name'])) { 
            $oldData = $this->getTypeAndCatById($id);
            
            $update = $this->update(self::TABLE_OPTIONS)
                           ->set(array(
                               'type' => $params['type'],
                               'name' => $params['name'],
                               'value' => $this->setValue($params)
                           ))
                           ->where(array('id' => $id));
            
            $result = $this->execute($update);
            
            if (
                $result &&
                isset($params['category_id']) &&
                (
                    (isset($oldData['type']) && $oldData['type'] != $params['type']) // check type
                    ||
                    (isset($oldData['cat_id']) && $oldData['cat_id'] != $params['category_id']) // check category
                )
               ){
                $ret = $this->addCategoryToOptions($params['category_id'], $id, true);
            }
        }
        return $ret;
    }
    
    /**
     * Set option value
     * @param array $params
     * @return string
     */
    private function setValue($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $value = '';
        
            if ($params !== null && isset($params['type'])) {
                $value = (
                    ($params['type'] == 'select' || $params['type'] == 'radio' || $params['type'] == 'multi')
                    ? (isset($params['option']) ? $this->jsonEncode($params['option']) : '[]') 
                    : (isset($params['default']) ? $params['default'] : '')
                );
            }
        
        return $value;
    }
    
    /**
     * Get option type end category
     * @param integer $id
     * @return null|string
     */
    private function getTypeAndCatById($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
            if((int)$id > 0){                
                $select = $this->select()
                            ->from(array('o' => self::TABLE_OPTIONS))
                            ->columns(array('type'))
                            ->join(
                                array('c2o' => self::TABLE_CATEGORY_TO_OPTION),
                                'c2o.option_id = o.id',
                                'cat_id'
                            )
                            ->where(array('o.id' => $id))
                            ->limit(1);

                $result = $this->fetchRowSelect($select);

                if($result){
                    $ret = $result;
                }
            }
        
        return $ret;
    }
    
    /**
     * Add category to options
     * @param integer $categoryId
     * @param integer $optionId
     * @param boolean $remove
     * @return boolean
     */
    private function addCategoryToOptions($categoryId = 0, $optionId = 0, $remove = false){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
            if((int)$categoryId > 0 && (int)$optionId > 0){
                if($remove === true){                    
                    $delete = $this->delete(self::TABLE_CATEGORY_TO_OPTION)
                                   ->where(array('option_id' => $optionId));
                    $this->execute($delete);
                }

                $insert = $this->insert(self::TABLE_CATEGORY_TO_OPTION)
                               ->values(array(
                                       'cat_id' => $categoryId,
                                       'option_id' => $optionId
                               ));
                $result = $this->execute($insert);

                if($result){
                    $ret = true;
                }
            }
        
        return $ret;
    }

    /**
     * Remove option
     * @param $id
     * @return bool
     */
    public function remove($id = array())
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($id > 0) {
            $delete = $this->delete(self::TABLE_OPTIONS)
                        ->where(array('id' => $id));
            $ret = (bool)$this->execute($delete);
        }
        return $ret;
    }

    /**
     * Get options for viewing ads
     * @param $advert_id
     * @return array|bool
     */
    public function getAdvertViewOptions($advert_id)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ((int)$advert_id > 0){
            $select = $this->select()
                           ->from(array('ao' => self::TABLE_ADVERTS_OPTIONS))
                           ->columns(array('value'))
                           ->join(
                               array('c2p' => self::TABLE_CATEGORY_TO_OPTION),
                               'c2p.id = ao.option_id',
                               array()
                           )
                           ->join(
                               array('op' => self::TABLE_OPTIONS),
                               'op.id = c2p.option_id',
                               array(
                                   'option_name' => 'name',
                                   'option_type' => 'type',
                                   'option_value' => 'value'
                               )
                           )
                           ->where(array('ao.advert_id' => $advert_id));
            
            $result = $this->fetchSelect($select);
            
            if ($result){
                foreach ($result as &$item){
                    if ($item['option_type'] == 'select' || $item['option_type'] == 'radio'|| $item['option_type'] == 'multi'){
                        $item['option_value'] = $this->jsonDecode($item['option_value']);
                        $item['value'] = $this->jsonDecode($item['value']);
                       
                        if(is_array($item['value'])){ // TODO: если мультиселект
                            $multiOptins = $item['option_value'];
                            foreach ($multiOptins as $key => $val){
                                if (!in_array($val['value'], $item['value'])){
                                    unset($item['option_value'][$key]); // TODO: удаляем не выбраные
                                }
                            }
                        }else{
                            foreach ($item['option_value'] as $val){
                                if ($val['value'] == $item['value']){
                                    $item['option_value'] = $val['name'];
                                }
                            }
                        }
                    } else {
                        $item['option_value'] = $item['value'];
                    }
                }
                
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * @param int $advert_id
     * @param null $options
     * @param int $category_id
     * @return bool
     */
    public function addAdvertOptions($advert_id = 0, $options = null, $category_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($advert_id > 0 && $options != null){
            foreach ($options as $key => $item) {
                $optId = $this->getCat2OptID($key, $category_id);
                $insert = $this->insert(self::TABLE_ADVERTS_OPTIONS)
                               ->values(array(
                                   'advert_id' => $advert_id,
                                   'option_id' => $optId,
                                   'value' => $item
                               ));
                $result = (bool)$this->execute($insert);
                
                if ($result){
                    $ret = $result;
                }
            }
        }
        return $ret;
    }

    /**
     * @param int $advert_id
     * @param null $options
     * @param int $category_id
     * @return bool
     */
    public function editAdvertOptions($advert_id = 0, $options = null, $category_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($advert_id > 0 && $options != null) {
            $delete = $this->delete(self::TABLE_ADVERTS_OPTIONS)
                           ->where(array('advert_id' => $advert_id));
            
            $this->execute($delete);
            
            $ret = $this->addAdvertOptions($advert_id, $options, $category_id);
        }
        return $ret;
    }

    /**
     * Get id from category to option table
     * @param int $option_id
     * @param int $category_id
     * @return bool|mixed
     */
    public function getCat2OptID($option_id = 0 , $category_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($option_id > 0 && $category_id > 0) {
            $select = $this->select(self::TABLE_CATEGORY_TO_OPTION)
                           ->columns(array('id'))
                           ->where(array(
                               'cat_id' => $this->load('AdvertCategory', 'admin')->getParentsArray($category_id),
                               'option_id' => $option_id
                           ))
                           ->limit(1);
            $result = $this->fetchOneSelect($select);
            
            if ($result){
                $ret =$result;
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
        $page = ($params['page'] > 0) ? $params['page'] : 1;
        
        $select = $this->getSQL();
        
        if($select instanceof \Zend\Db\Sql\Select){

            $select->columns(array(
                        'count' => $this->expr('count(*)')
                     ));
            
            // set params
            $select = $this->setParams($select, $params);
            
            $count = (int)$this->fetchOneSelect($select);
        }

        return $this->paginator($page, $count, self::OPTIONS_PER_PAGE);
    }
}