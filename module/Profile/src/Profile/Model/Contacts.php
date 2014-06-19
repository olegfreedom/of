<?php
namespace Profile\Model;

class Contacts extends \Application\Base\Model
{
    /**
     * Get User Info
     * @param int $userId
     * @return array
     */
    public function get($userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
            if((int)$userId > 0){
                $select = $this->select()
                               ->from(self::TABLE_USER)
                               ->columns(array(
                                   'name'
                               ))
                               ->where(array(
                                   'id' => $userId
                               ))
                               ->limit(1);
                
                $result = $this->fetchRowSelect($select);
                
                if($result){
                    $ret = $result;
                }
            }
        
        return $ret;
    }
    
    /**
     * Edit User Info
     * @param array $params
     * @param array $arrays
     * @param int $userId
     * @return bool
     */
    public function edit($params = null, $arrays = null, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
            if($params !== null && $userId > 0){
                
                $update = $this->update(self::TABLE_USER)
                               ->set($params)
                               ->where(array('id' => $userId));
                
                $ret = $this->execute($update);
                
                if(isset($arrays['phone'])){
                    $maskArray = isset($arrays['mask']) ? $arrays['mask'] : null;
                    $this->load('UsersPhone', 'admin')->add($userId, $arrays['phone'], $maskArray);
                } 
            }
        
        return (bool)$ret;
    }
}