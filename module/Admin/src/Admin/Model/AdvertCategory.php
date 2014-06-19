<?php
namespace Admin\Model;

class AdvertCategory extends \Application\Base\Model
{
    protected static $breadcrumbsArray = null;
    protected static $parentsArray = null;
    protected static $subIdArray = null;

    /**
     * Get categories
     * @return array|null
     */
    public function get($whereAr = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
                     
        $select = $this->select()
                       ->from(self::TABLE_ADVERTS_CATEGORIES)
                       ->columns(array(
                           'id', 
                           'name',
                           'url',
                           'parent_id'
                       ))
                       ->where(array_merge($whereAr, array(
                           $this->where()->greaterThan('id', 1)
                       )))
                       ->order('id asc');

        $result = $this->fetchSelect($select);

        if($result){
            $ret = $this->generateCategoryList($result);
        }
        
        return $ret;
    }
    
    /**
     * Recursively generate category
     * @param array $array
     * @param integer $parent
     * @return array|null
     */
    private function generateCategoryList($array = array(), $parent = 1){        
        $ret = null;
        
        if(is_array($array)){
            foreach($array as $item){
                if($item['parent_id'] == $parent){
                    if($ret === null){
                        $ret = array();
                    }
                    /* search sub category */
                    $sub = $this->generateCategoryList($array, $item['id']);
                    if(is_array($sub)){
                        $item['subcategory'] = $sub;
                    }
                    $ret[] = $item;
                }
            }            
        }
        
        return $ret;
    }

    /**
     * get one category
     * @param integer $id
     * @return array
     */
    public function getOne($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $res = null;
        
        if($id > 1){
            $select = $this->select()
                    ->from(self::TABLE_ADVERTS_CATEGORIES)
                    ->columns(array(
                        'id', 
                        'name',
                        'url',
                        'parent_id'
                    ))
                    ->where(array('id' => $id))
                    ->limit(1);    

            $result = $this->fetchRowSelect($select);
            
            if($result){
                $res = $result;
            }
        }
        
        return $res;
    }
       
    /**
     * Edit category
     * @param integer $id
     * @param array $params
     * @return bool
     */
    public function edit($id = 0, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;

        if($id > 1 && $params !== null && isset($params['name'])){
            $params['url'] = $this->translit($params['name']);
            
            $update = $this->update(self::TABLE_ADVERTS_CATEGORIES)
                           ->set($params)
                           ->where(array('id' => $id));

            $ret = $this->execute($update);
            
            $this->checkUrl($params['url'], $id);
        }
        
        return (bool)$ret;
    }
    
    /**
     * Add category
     * @param array $params
     * @return bool
     */
    public function add($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;

        if($params !== null && isset($params['name'])){
            $params['url'] = $this->translit($params['name']);

            $insert = $this->insert(self::TABLE_ADVERTS_CATEGORIES)
                           ->values($params);

            $ret = $this->execute($insert);
            $id = $this->insertId();
            
            $this->checkUrl($params['url'], $id);
        }
        
        return (bool)$ret;
    }
    
    /**
     * Check and update url
     * @param string $url
     * @param integer $id
     */
    private function checkUrl($url = null, $id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if($url !== null && !empty($url) && $id > 0){
            $select = $this->select()
                           ->from(self::TABLE_ADVERTS_CATEGORIES)
                           ->columns(array('id'))
                           ->where(array(
                               'url' => $url,
                               $this->where()->notEqualTo('id', $id)
                           ))
                           ->limit(1);
            
            $result = $this->fetchOneSelect($select);
            
            if($result){
                $update = $this->update(self::TABLE_ADVERTS_CATEGORIES)
                               ->set(array('url' => ($url.'-'.$id)))
                               ->where(array('id' => $id));
                
                $this->execute($update);
            }
        }
    }


    /**
     * Remove check
     * @param integer $id
     * @return bool
     */
    public function checkKeys($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        return $this->load('ForeignKeys', 'admin')->check(self::TABLE_ADVERTS_CATEGORIES, $id);
    }
    
    /**
     * Remove category
     * @param integer $id
     * @return bool
     */
    public function remove($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($id > 1 && $this->checkKeys($id) == false){            
            $delete = $this->delete(self::TABLE_ADVERTS_CATEGORIES)
                           ->where(array('id' => $id));
            
            $ret = $this->execute($delete);
        }
        
        return (bool)$ret;
    }
    
    /**
     * Get breadcrumbs array
     * @param integer $id
     * @return null|array
     */
    public function getBreadcrumbsArray($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;

        if((int)$id > 0){
            if(self::$breadcrumbsArray === null){                
                $select = $this->select()
                               ->from(self::TABLE_ADVERTS_CATEGORIES)
                               ->columns(array(
                                   'id',
                                   'name',
                                   'parent_id'
                               ))
                               ->where(array(
                                   $this->where()->greaterThan('id', 1)
                               ))
                               ->order('id asc');

                $result = $this->fetchSelect($select);
                
                if($result){
                    self::$breadcrumbsArray = $result;
                }
            }
            
            $ret = $this->generateBreadcrumbsArray(self::$breadcrumbsArray, $id);            
        }
        
        return $ret;
    }
    
    /**
     * Recursively generate breadcrumbs
     * @param array $array
     * @param integer $id
     * @return null|array
     */
    private function generateBreadcrumbsArray($array = array(), $id = 1){
        $ret = null;
        
        if(is_array($array) && $id > 1){
            foreach($array as $item){
                if($item['id'] == $id){
                    if($ret === null){
                        $ret = array();
                    }
                    
                    /* search parent category */
                    $parent = $this->generateBreadcrumbsArray($array, $item['parent_id']);
                    if(is_array($parent)){
                       $ret = $parent;
                    }
                    
                    $ret[] = array(
                                'id' => $item['id'],
                                'name' => $item['name']
                             );
                }
            }            
        }
        
        return $ret;
    }
    
    /**
     * Get parents array
     * @param integer $id
     * @return null|array
     */
    public function getParentsArray($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;

        if((int)$id > 0){
            if(self::$parentsArray === null){                
                $select = $this->select()
                               ->from(self::TABLE_ADVERTS_CATEGORIES)
                               ->columns(array(
                                   'id',
                                   'parent_id'
                               ))
                               ->where(array(
                                   $this->where()->greaterThan('id', 1)
                               ))
                               ->order('id asc');

                $result = $this->fetchSelect($select);
                
                if($result){
                    self::$parentsArray = $result;
                }
            }
            
            $ret = $this->generateParentsArray(self::$parentsArray, $id);            
        }
        
        return $ret;
    }
    
    /**
     * Recursively generate parents
     * @param array $array
     * @param integer $id
     * @return null|array
     */
    private function generateParentsArray($array = array(), $id = 1){
        $ret = null;
        
        if(is_array($array) && $id > 1){
            foreach($array as $item){
                if($item['id'] == $id){
                    if($ret === null){
                        $ret = array();
                    }
                    
                    /* search parent category */
                    $parent = $this->generateParentsArray($array, $item['parent_id']);
                    if(is_array($parent)){
                       $ret = $parent;
                    }
                    
                    $ret[] = $item['id'];
                }
            }            
        }
        
        return $ret;
    }
  
    /**
     * Get sub array
     * @param integer $id
     * @return array
     */
    public function getSubIdArray($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = array();

        if((int)$id > 0){
            if(self::$subIdArray === null){                
                $select = $this->select()
                               ->from(self::TABLE_ADVERTS_CATEGORIES)
                               ->columns(array(
                                   'id', 
                                   'parent_id'
                               ))
                               ->where(array(
                                   $this->where()->greaterThan('id', 1)
                               ))
                               ->order('id asc');

                $result = $this->fetchSelect($select);

                if($result){
                    self::$subIdArray = $result;
                }
            }

            $sub = $this->generateSubIdArray(self::$subIdArray, $id);
            if(is_array($sub)){
                $ret = $sub;
            }

            $ret[] = $id;
        }
        
        return $ret;
    }
    
    /**
     * Recursively generate sub id
     * @param array $array
     * @param integer $parent
     * @return null|array
     */
    private function generateSubIdArray($array = array(), $parent = 1){
        $ret = null;
        
        if(is_array($array)){
            foreach($array as $item){
                if($item['parent_id'] == $parent){
                    if($ret === null){
                        $ret = array();
                    }
                    
                    /* search sub category */
                    $sub = $this->generateSubIdArray($array, $item['id']);
                    if(is_array($sub)){
                       $ret = $sub;
                    }
                    
                    $ret[] = $item['id'];
                }
            }            
        }
        
        return $ret;
    }
}
