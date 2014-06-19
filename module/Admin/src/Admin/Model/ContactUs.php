<?php
namespace Admin\Model;

class ContactUs extends \Application\Base\Model {
    
    const POST_PER_PAGE = 20;
    
    public function getSQL() {
        $select = $this->select()
                       ->from(self::TABLE_CONTACT_US)
                       ->columns(array(
                           'id',
                           'name',
                           'email',
                           'message',
                           'date' => $this->expr('date_format(timestamp, "%d.%m.%Y %H:%i")')
                       ))
                       ->order('timestamp desc');
        
        return $select;
    }
    
    /**
     * Get list of user messages
     * @param array $params
     * @return null|array
     */
    public function getList($params) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        $select = $this->getSQL();
        
        if(isset($params['page']) && (int)$params['page'] > 0){
            $select->limitPage($params['page'], self::POST_PER_PAGE);
        }

        $result = $this->fetchSelect($select);
            
        $ret = $result; 

        return $ret;
        
    }
    
    /**
     * Get one
     * @param integer $id
     * @return array|null
     */
    public function getOne($id = 0) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $select = $this->getSQL();
        
        $select->where(array('id' => $id))
               ->limit(1);
        
        $result = $this->fetchRowSelect($select);
        
        return $result;
    }
    
    
    /**
     * Edit
     * @param integer $id
     * @param array $params
     * @return boolean
     */
    public function edit($id = 0, $params = null) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($id > 0 && $params !== null) {
            $update = $this->update(self::TABLE_CONTACT_US)
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
            $delete = $this->delete(self::TABLE_CONTACT_US)
                           ->where(array('id' => $id));

            $ret = $this->execute($delete);
        }
        
        return (bool)$ret;
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
        
        $select->columns(array(
                    'count' => $this->expr('count(*)')
               ));
            
        $count = (int)$this->fetchOneSelect($select);

        return $this->paginator($page, $count, self::POST_PER_PAGE);
    }
    
}

