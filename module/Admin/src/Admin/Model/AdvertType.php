<?php
namespace Admin\Model;

class AdvertType extends \Application\Base\Model
{
    private $defaultTypeId;

    static public $table = self::TABLE_ADVERTS_TYPE;
    static public $columns = array('id', 'name');
    static public $order = 'id asc';
    
    /**
     * Get one type
     * @param int $id
     * @return null|array
     */
    public function getTypeById($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        if($id > 0){
            $select = $this->select()
                           ->from(self::TABLE_ADVERTS)
                           ->columns(array('type'))
                           ->where(array('id' => $id))
                           ->limit(1);    
            
            $result = $this->fetchOneSelect($select);

            if($result){
                $ret = $result;
            }
        }

        return $ret;
    }    
    
    /**
     * Get default type id
     * @return null|integer
     */
    public function getDefaultTypeId(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if($this->defaultTypeId === null){            
            $select = $this->select()
                           ->from(self::TABLE_ADVERTS_TYPE)
                           ->columns(array('id'))
                           ->order('name desc')
                           ->limit(1);    

            $result = (int)$this->fetchOneSelect($select);

            if($result > 0){
                $this->defaultTypeId = $result;
            }
        }

        return $this->defaultTypeId;
    }    
}
