<?php
namespace Admin\Model;

class AdvertPhone extends Phone
{

    /**
     * Get all list
     * @param int $advert
     * @return null|array
     */
    public function get($advert = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        if((int)$advert > 0){
            $select = $this->select()
                           ->from(array('p' => self::TABLE_ADVERTS_PHONE))
                           ->columns(array(
                                'id',
                                'phone'
                           ))
                           ->join(
                               array('a' => self::TABLE_ADVERTS),
                               'p.advert_id = a.id',
                               array()
                           )
                           ->join(
                               array('u' => self::TABLE_USER),
                               'a.user_id = u.userid',
                               array()
                           )
                           ->join(
                               array('pm' => self::TABLE_PHONE_MASK),
                               'p.mask_id = pm.id',
                               'mask'
                           )
                           ->where(array(
                               'p.advert_id' => $advert
                           ))
                           ->order('p.id asc');

            $result = $this->fetchSelect($select);

            if($result){ 
               foreach($result as &$item){
                   $item['phone'] = $this->formatPhone($item['phone'], $item['mask']);
               }

               $ret = $result; 
            }
        }
        
        return $ret;
    }   
    
    /**
     * Add
     * @param int $advert
     * @param array $phone
     * @param array $maskArray
     * @return bool
     */
    public function add($advert = 0, $phone = null, $maskArray = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if((int)$advert > 0 && is_array($phone)){ 
            $size = sizeof($phone);
            
            if($size > 0){
                $params = array(
                    'advert_id' => $advert
                );
                
                for($i=0; $i<$size; $i++){ 
                    if($this->checkPhone($phone[$i]) === true){
                        $params['phone'] = $this->cleanupPhone($phone[$i]);
                        
                        if(isset($maskArray[$i])){
                            $params['mask_id'] = $maskArray[$i];
                        }
                        
                        $insert = $this->insert(self::TABLE_ADVERTS_PHONE)
                                       ->values($params);

                        $this->execute($insert);

                        $ret = true;
                    }
                }  
            }            
        }
        
        return $ret;
    }
    
    /**
     * Remove
     * @param int $advert
     * @param int $id
     * @param int $userId
     * @return bool
     */
    public function remove($advert = 0, $id = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if((int)$advert > 0){                                
            $delete = $this->delete(self::TABLE_ADVERTS_PHONE)
                           ->where(array(
                               'advert_id' => $advert
                           ));

            if((int)$id > 0){
                $delete->where(array('id' => $id));
            }

            if((int)$userId > 0){
                if($this->checkUserAccess($advert, $userId) == true){
                    $ret = $this->execute($delete);
                }
            }else{
                $ret = $this->execute($delete);
            }
        }
        
        return (bool)$ret;
    }
    
    /**
     * Remove access
     * @param int $advert
     * @param int $userId
     * @return bool
     */
    private function checkUserAccess($advert = 0, $userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
            if($advert > 0 && $userId > 0){
                $select = $this->select()
                               ->from(self::TABLE_ADVERTS)
                               ->columns(array('id'))
                               ->where(array(
                                   'id' => $advert,
                                   'user_id' => $userId
                               ))
                               ->limit(1);

                $result = (int)$this->fetchOneSelect($select);

                if($result > 0){
                    $ret = true;
                }
            }
        
        return $ret;
    }
}