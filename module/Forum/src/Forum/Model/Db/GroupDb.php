<?php
namespace Forum\Model\Db;

class GroupDb extends \Application\Base\Model
{    
    protected $_tableName = 'forum_group';
    /**
     * work with group
     * @param int $data
     * @return boolean
     */
    public function insertData($params = null){
        $this->log(__CLASS__.'\\'.__FUNCTION__);   
            $insert = $this->insert($this->_tableName)
                           ->values($params);
        
            $result = $this->execute($insert);
        if(!$result) 
            $result = false;
        else
            $result = true;
        return $result; 
    }
    
    public function getData($paramsColumnt = array('*'),$paramsWhere = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $where = array();
        if(isset($paramsWhere['id']))
            $where[$this->_tableName.'.id'] = $paramsWhere['id'];
        $select = $this->select()
                    ->columns($paramsColumnt)
                    ->from($this->_tableName)
                    ->join(array('to'=>'forum_type_organization'),'to.id = '.$this->_tableName.'.type_organization_id',array('type_organization_title'=>'title'))
                    ->join(array('u'=>'vb_user'),'u.userid = '.$this->_tableName.'.id_user',array('username'))
                    ->where($where);
         $result = $this->fetchSelect($select);

        if(!$result)
            $result = false;
        return $result;
    }
    
    public function updateData($data,$where){
        if(empty($where)||empty($data)) return false;
        $update = $this->update($this->_tableName)
                       ->set($data)
                       ->where($where);

        $result = $this->execute($update);

        if(!$result) 
            $result = false;
        else
            $result = true;
        return $result;
    }
    
    public function deleteData(array $params){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $delete = $this->delete($this->_tableName)
                        ->where($params);
        $result = $this->execute($delete);
        if(!$result)
            $result = false;
        else
            $result = true;
        return $result;
    }
}
?>
