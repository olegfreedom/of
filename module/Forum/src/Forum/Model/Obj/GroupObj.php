<?php
namespace Forum\Model\Obj;
use Forum\Model\Db;
use Forum\Model;
class GroupObj
{    
    /* variable */
    protected $_id;
    protected $_idUser;
    protected $_typeOrganizationId;
    protected $_title;
    protected $_description;
    protected $_active;
    protected $_avaliable;
    protected $_creation;
    protected $_avatar;
    
    protected $_avatarUrl;
    /*   save link to Db   */
    protected $DB;
    // --------------------------------------------------------------------------------------------
    public function __construct() {
//        $this->_creation = date('Y-m-d m:h:s');
        $this->DB = new Db\GroupDb();
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
        if(isset($data['type_organization_id']))
            $this->_typeOrganizationId = $data['type_organization_id'];
        if(isset($data['title']))
            $this->_title = $data['title'];
        if(isset($data['description']))
            $this->_description = $data['description'];
        if(isset($data['active']))
            $this->_active = $data['active'];
        if(isset($data['avatar']))
            $this->_avatar = $data['avatar'];
        if(isset($data['avaliable']))
            $this->_avaliable = $data['avaliable'];
        if(isset($data['creation']))
            $this->_creation = $data['creation'];
        return $this;
    }
    
    public function getData(){
        $this->_avatarUrl = $this->getAvatarUrl();

        // ---------------------- prepare get data ------------------------------------------------
        $dataArray = array(
            'id'       => $this->_id,
            'id_user'   => $this->_idUser,
            'type_organization_id'  => $this->_typeOrganizationId,
            'title'     => $this->_title,
            'description'   => $this->_description,
            'active'    => $this->_active,
            'avatar'    => $this->_avatar,
            'avaliable' => $this->_avaliable,
            'creation'  => $this->_creation,
            'avatar'    => $this->_avatar,
            'avatar_url'    => $this->_avatarUrl,
        );
        return $dataArray;
    }
    
    public function getFillData(){
        $fillData = array();
        if(isset($this->_id))
            $fillData['id'] = $this->_id;
        if(isset($this->_idUser))
            $fillData['id_user'] = $this->_idUser;
        if(isset($this->_typeOrganizationId))
            $fillData['type_organization_id'] = $this->_typeOrganizationId;
        if(isset($this->_title))
            $fillData['title'] = $this->_title;
        if(isset($this->_description))
            $fillData['description'] = $this->_description;
        if(isset($this->_active))
            $fillData['active'] = $this->_active;
        if(isset($this->_avatar))
            $fillData['avatar'] = $this->_avatar;
        if(isset($this->_avaliable))
            $fillData['avaliable'] = $this->_avaliable;
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
        if(isset($param['id']))
            $where['id'] = $param['id'];

        $resultDb = $this->DB->getData(array('*'),$where);

        if(!empty($resultDb)){
            foreach($resultDb as $key => $value){
                $resultDb[$key]['avatar_url'] = $this->getAvatarUrl($value['avatar']);
            }
        }
        return $resultDb;
    }
    
    public function clearData(){
        
    }
    // --------------------------------------------------------------------------------------------
    private function getAvatarUrl($avatarName = null){
        $avatar ='';
        if(isset($avatarName))
            $avatar = $avatarName;
        if(isset($this->_avatar))
            $avatar = $this->_avatar;
        $upload = new Model\Upload();   
        $url = $upload ->existFile($avatar, '/data/forum/avatars');
        if(!$url)
            $url = '/data/forum/avatars/default.jpg';
        return $url;
    }

    private function deleteAvatar($avatarNmae = null){
        if(isset($avatarName))
            $avatar = $avatarName;
        if(isset($this->_avatar))
            $avatar = $this->_avatar;
        $upload = new Model\Upload();   
        $url = $upload ->existFile($avatar, '/data/forum/avatars');
        return $upload ->unlink($url);
    }
}
?>