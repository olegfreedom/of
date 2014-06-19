<?php
namespace Forum\Model\Obj;
use Forum\Model\Db;
use Forum\Model;
class ThemeObj
{    
    /* variable */
    protected $_id;
    protected $_idForumGroup;
    protected $_title;
    protected $_description;
    protected $_creation;
    protected $_status;
    protected $_idUser;
    protected $_visibility;
    protected $_beginDate;
    protected $_endDate;
    protected $_type;

    /*   save link to Db   */
    protected $DB;
    // --------------------------------------------------------------------------------------------
    public function __construct() {
//        $this->_creation = date('Y-m-d m:h:s');
        $this->DB = new Db\ThemeDb();
    }
    public function initial(array $params ){
        $paramWhere = array();
        if(isset($params['id']))
            $paramWhere['id'] = $params['id'];

        $resultDb = $this->DB->getData(array('*'),$paramWhere);
        
        if(!empty($resultDb))
            $this->setData($resultDb[0]);
    }
            
    public function setData(array $data = array()){  
    
        if(isset($data['id']))
            $this->_id = $data['id'];
        if(isset($data['id_forum_group']))
            $this->_idForumGroup = $data['id_forum_group'];
        if(isset($data['title']))
            $this->_title = $data['title'];
        if(isset($data['description']))
            $this->_description = $data['description'];
        if(isset($data['creation']))
            $this->_creation = $data['creation'];
        if(isset($data['status']))
            $this->_status = $data['status'];
        if(isset($data['id_user']))
            $this->_idUser = $data['id_user'];
        if(isset($data['visibility']))
            $this->_visibility = $data['visibility'];
        if(isset($data['begin_date']))
            $this->_beginDate = $data['begin_date'];
        if(isset($data['end_date']))
            $this->_endDate = $data['end_date'];
        if(isset($data['type']))
            $this->_type = $data['type'];
        
        return $this;
    }
    
    public function getData(){
        // ---------------------- prepare get data ------------------------------------------------
        $dataArray = array(
            'id'       => $this->_id,
            'id_forum_group'   => $this->_idForumGroup,
            'title'     => $this->_title,
            'description'   => $this->_description,
            'status'    => $this->_status,
            'id_user'    => $this->_idUser,
            'visibility' => $this->_visibility,
            'begin_date'  => $this->_beginDate,
            'end_date'    => $this->_endDate,
            'type'    => $this->_type,
            'creation' => $this->_creation,
        );
        return $dataArray;
    }
    
    public function getFillData(){
        $fillData = array();
        if(isset($this->_id))
            $fillData['id'] = $this->_id;
        if(isset($this->_idUser))
            $fillData['id_user'] = $this->_idUser;
        if(isset($this->_idForumGroup))
            $fillData['id_forum_group'] = $this->_idForumGroup;
        if(isset($this->_title))
            $fillData['title'] = $this->_title;
        if(isset($this->_description))
            $fillData['description'] = $this->_description;
        if(isset($this->_status))
            $fillData['status'] = $this->_status;
        if(isset($this->_visibility))
            $fillData['visibility'] = $this->_visibility;
        if(isset($this->_beginDate))
            $fillData['begin_date'] = $this->_beginDate;
        if(isset($this->_endDate))
            $fillData['end_date'] = $this->_endDate;
        if(isset($this->_creation))
            $fillData['creation'] = $this->_creation;
        if(isset($this->_type))
            $fillData['type'] = $this->_type;
        
        return $fillData;
    }
    
    public function save(array $data = array()){
        
        if(empty($data))
            $data = $this->getFillData(); 
        return $this->DB->insertData($data);
    }
   
    public function update(array $data = array(), array $where = array()){
        if(empty($data))
            $data = $this->getFillData(); 
        if(empty($where))
            $where = array('id' => $this->_id);
        //  change avatar   
        return $this->DB->updateData($data,$where);
    }
    
    public function delete(array $paramsWhere = array()){
        if(empty($paramsWhere)){
            $paramsWhere = array('id' => $this->_id);
        }
        // get url avatar and delete avatar
        return $this->DB->deleteData($paramsWhere);
    }
    
    public function getDataList(array $param = array()){
        $where = array();
        if(!empty($param['id_forum_group']))
            $where['id_forum_group'] = $param['id_forum_group'];
        
        $resultDb = $this->DB->getData(array('*'), $where);
        return $resultDb;
    }
}
?>