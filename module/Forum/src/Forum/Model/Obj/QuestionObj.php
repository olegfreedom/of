<?php
namespace Forum\Model\Obj;
use Forum\Model\Db\QuestionDb;
use Forum\Model\Db\CommentDb;
use Forum\Model;
class QuestionObj
{    
    /* variable */
    protected $_id;
    protected $_idTheme;
    protected $_title;
    protected $_description;
    protected $_idUser;
    protected $_creation;
    protected $_status;
    protected $_raiting;
    protected $_countUserVote;

    /*   save link to Db   */
    protected $DB;
    // --------------------------------------------------------------------------------------------
    public function __construct() {
//        $this->_creation = date('Y-m-d m:h:s');
        $this->DB = new QuestionDb();
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
        if(isset($data['id_theme']))
            $this->_idTheme = $data['id_theme'];
        if(isset($data['title']))
            $this->_title = $data['title'];
        if(isset($data['description']))
            $this->_description = $data['description'];
        if(isset($data['id_user']))
            $this->_idUser = $data['id_user'];
        if(isset($data['creation']))
            $this->_creation = $data['creation'];
        if(isset($data['status']))
            $this->_status = $data['status'];       
        if(isset($data['raiting']))
            $this->_raiting = $data['raiting'];
        if(isset($data['count_user_vote']))
            $this->_countUserVote = $data['count_user_vote'];
        
        return $this;
    }
    
    public function getData(){
        // ---------------------- prepare get data ------------------------------------------------
        
        $dataArray = array(
            'id'       => $this->_id,
            'id_theme'   => $this->_idTheme,
            'title'     => $this->_title,
            'description'   => $this->_description,
            'id_user'    => $this->_idUser,
            'creation' => $this->_creation,
            'status'    => $this->_status,
            'raiting' => $this->_raiting,
            'count_user_vote'  => $this->_countUserVote,
        );
        return $dataArray;
    }
    
    public function getFillData(){
        $fillData = array();
        if(isset($this->_id))
            $fillData['id'] = $this->_id;
        if(isset($this->_idUser))
            $fillData['id_user'] = $this->_idUser;
        if(isset($this->_idTheme))
            $fillData['id_theme'] = $this->_idTheme;
        if(isset($this->_title))
            $fillData['title'] = $this->_title;
        if(isset($this->_description))
            $fillData['description'] = $this->_description;
        if(isset($this->_status))
            $fillData['status'] = $this->_status;
        if(isset($this->_creation))
            $fillData['creation'] = $this->_creation;
        if(isset($this->_raiting))
            $fillData['raiting'] = $this->_raiting;
        if(isset($this->_countUserVote))
            $fillData['count_user_vote'] = $this->_countUserVote;

        
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
        if(is_array($resultDb)){
            $dbComment = new CommentDb();
            foreach($resultDb as $key => $value){
                $resultDb[$key]['comments'] = $dbComment->getData(array('*'),array('id_question' => $value['id']));
            }
        }
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