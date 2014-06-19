<?php
namespace Admin\Model;

class Testimonials extends \Application\Base\Model {
    
    const POST_PER_PAGE = 20;

    public static $typeNames = array(
        'grate' => 'Благодарность',
        'advice' => 'Предложение',
        'complaint' => 'Жалоба',
        'advert' => 'Объявление: Отзыв'
    );
    
    public function getSQL() {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $select = $this->select()
                       ->from(self::TABLE_TESTIMONIALS)
                       ->columns(array(
                            'id',
                            'name',
                            'email',
                            'message',
                            'rating',
                            'type',
                            'active',
                            'date' => $this->expr('date_format(timestamp, "%d.%m.%Y %H:%i")')
                        ))
                        ->order('timestamp desc');
        
        return $select;
    }

    /**
     * Get all list
     * @return null|array
     */
    public function getList($params){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;
        $select = $this->getSQL();

        if(isset($params['type']) && !empty($params['type'])) {
            $select->where(array('type' => $params['type']));
        }
        
        if(isset($params['page'])){
            $select->limitPage($params['page'], self::POST_PER_PAGE);
        }

        $result = $this->fetchSelect($select);
        
        if($result){
            foreach ($result as $key=>$value) {
                $result[$key]['type_tr'] = $this->getTypeName($value['type']);
            }
            $ret = $result;
        }

        return $ret;
    }
    
    /**
     * get paginator
     * @param array $params search params
     * @return null|array
     */
    public function getPaginator($params = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $count = 0;
        $page = isset($params['page']) ? $params['page'] : 1; 
        
        $select = $this->getSQL();
        
        if(isset($params['type']) && $params['type'] !== '') {
            $select->where(array('type' => $params['type']));
        }
            
        $select->columns(array(
                    'count' => $this->expr('count(*)')
               ));
            
        $count = (int)$this->fetchOneSelect($select);

        return $this->paginator($page, $count, self::POST_PER_PAGE);
    }
    
    
    /**
     * Edit testimonial
     * @param integer $id
     * @param array $params
     * @return boolean
     */
    public function edit($id = 0, $params = null) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($id > 0 && $params !== null) {
            $update = $this->update(self::TABLE_TESTIMONIALS)
                           ->set($params)
                           ->where(array('id' => $id));
            
            $ret = $this->execute($update);
        }
          
        return (bool)$ret;
    }
    
    /**
     * Remove
     * @param integer $id
     * @return boolean
     */
    public function remove($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($id > 0){ 
            $delete = $this->delete(self::TABLE_TESTIMONIALS)
                           ->where(array('id' => $id));

            $ret = $this->execute($delete);
        }
        
        return (bool)$ret;
    }
    
    /**
     * Get one testimonial record
     * @param integer $id
     * @return array|null
     */
    public function getOne($id = 0) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $select = $this->getSQL();
        
        $select->where(array(
            'id' => $id
        ))
                ->limit(1);
        
        $result = $this->fetchRowSelect($select);
        
        return $result;
    }
    
    public function setTestimonialStatus($id = 0) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if((int)$id > 0) {
        
            $select = $this->select()
                   ->from(self::TABLE_TESTIMONIALS)
                   ->columns(array(
                        'active'
                    ))
                   ->where(array('id' => $id))
                   ->limit(1);

            $active = $this->fetchOneSelect($select);

            if(isset($active)) {
                $update = $this->update(self::TABLE_TESTIMONIALS)
                               ->set(array(
                                     'active' => ($active == 'y' ? 'n' : 'y')
                                     ))
                               ->where(array('id' => $id));
                }
        }
        $ret = $this->execute($update); 
    }
    
    
    public function getTypeName($type = null) {

        if(isset($type)) {
            return self::$typeNames[$type];
        }
    }
}

