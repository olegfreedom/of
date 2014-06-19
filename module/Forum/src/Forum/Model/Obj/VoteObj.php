<?php
namespace Forum\Model\Obj;
use Forum\Model\Db\VoteDb;
use Forum\Model;
class VoteObj
{    
    /* variable */
    protected $_id;
    protected $_idUser;
    protected $_idQuestion;
    protected $_vote;
    protected $_creation;
    /*   save link to Db   */
    protected $DB;
    // --------------------------------------------------------------------------------------------
    public function __construct() {
//        $this->_creation = date('Y-m-d m:h:s');
        $this->DB = new VoteDb();
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
        if(isset($data['id_user']))
            $this->_idUser = $data['id_user'];
        if(isset($data['id_question']))
            $this->_idQuestion = $data['id_question'];
        if(isset($data['vote']))
            $this->_vote = $data['vote'];
        if(isset($data['creation']))
            $this->_creation = $data['creation'];
        return $this;
    }
    
    public function getData(){
        // ---------------------- prepare get data ------------------------------------------------
        
        $dataArray = array(
            'id'        => $this->_id,
            'id_user'   => $this->_idUser,
            'id_question'   =>$this->_idQuestion,
            'vote'          =>  $this->_vote,
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
        if(isset($this->_idQuestion))
            $fillData['id_question'] = $this->_idQuestion;
        if(isset($this->_vote))
            $fillData['vote'] = $this->_vote;
        if(isset($this->_creation))
            $fillData['creation'] = $this->_creation;
        
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
        if(isset($param['id_theme']))
            $where['id_theme'] = $param['id_theme'];
        
        $resultDb = $this->DB->getData(array('*'),$where);
        return $resultDb;
    }
    
    public function getLastInsertData(){
        $resultDb = $this->DB->getLastData();
        return $resultDb;
    }
    
    public function getCount($params){
        $resultDb = $this->DB->getCount($params);
        return $resultDb;
    }
}
?>