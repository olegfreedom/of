<?php
namespace Forum\Model\Db;

class CommentDb extends \Application\Base\Model
{    
    protected $_tableName = 'forum_question_comment';
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
          $where = array();
        foreach($paramsWhere as $key => $value){
            $where[$this->_tableName.'.'.$key] = $value;
        }
        
        $select = $this->select()
                    ->columns($paramsColumnt)
                    ->from($this->_tableName)
                    ->join(array('u'=>'user'),'u.id = '.$this->_tableName.'.id_user',array('firstname','lastname','secondname'))
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
    
    public function getLastData(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $select = $this->select()
            ->columns(array("*"))
            ->from($this->_tableName)
            ->join(array('u'=>'user'),'u.id = '.$this->_tableName.'.id_user',array('firstname','lastname','secondname'))
            ->order('id DESC')
            ->limit(1);
         $result = $this->fetchRowSelect($select);
        if(!$result)
            $result = false;
        return $result;
    }
}
?>
