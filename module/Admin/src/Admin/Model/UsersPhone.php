<?php
namespace Admin\Model;

class UsersPhone extends Phone
{    
    /**
     * Get all list
     * @param int $userId
     * @return null|array
     */
    public function get($userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        if($userId > 0){
            $select = $this->select()
                           ->from(array('p' => self::TABLE_USER_PHONE))
                           ->columns(array(
                                'id',
                                'phone'
                           ))
                           ->join(
                               array('pm' => self::TABLE_PHONE_MASK),
                               'p.mask = pm.id',
                               array(
                                   'mask',
                                   'mask_id' => 'id'
                               )
                           )
                           ->where(array(
                               'p.user_id' => $userId
                           ))
                           ->order('p.id asc');

            $result = $this->fetchSelect($select);

            if($result){ 
               foreach($result as &$item){
                   $item['phone'] = $this->formatPhone($item['phone'], $item['mask']);
                   $item['placeholder'] = $this->getPlaceholder($item['mask']);
               }

               $ret = $result; 
            }
        }
        
        return $ret;
    }   
    
    /**
     * Add
     * @param int $userId
     * @param array $phone
     * @param array $maskArray
     * @return bool
     */
    public function add($userId = 0, $phone = null, $maskArray = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($userId > 0 && $phone !== null){ 
            $size = sizeof($phone);
            
            if($size > 0){
                $params = array(
                    'user_id' => $userId
                );
                
                for($i=0; $i<$size; $i++){ 
                    if($this->checkPhone($phone[$i]) === true){
                        $params['phone'] = $this->cleanupPhone($phone[$i]);
                        
                        if(isset($maskArray[$i])){
                            $params['mask'] = $maskArray[$i];
                        }
                        
                        $insert = $this->insert(self::TABLE_USER_PHONE)
                                       ->values($params);

                        $this->execute($insert);

                        if($ret == false){
                            $ret = true;
                        }
                    }

                }
                
            }            
        }
        
        return (bool)$ret;
    }
    
    /**
     * Remove
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function remove($id = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
                                       
        if($id > 0){
            $delete = $this->delete(self::TABLE_USER_PHONE)
                           ->where(array(
                               'id' => $id
                           ));

            if($userId > 0){
                $delete->where(array('user_id' => $userId));
            }
            
            $ret = $this->execute($delete);
        }
        
        return (bool)$ret;
    }
}