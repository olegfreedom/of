<?php
namespace Admin\Model;

class Phone extends \Application\Base\Model
{
    const PHONE_MASK = 'd (ddd) ddd-dd-dd';
    
    /**
     * 
     * @param string $phone
     * @return string
     */
    public function cleanupPhone($phone = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        return preg_replace('/[^0-9]/', '', $phone);
    }
    
    /**
     * 
     * @param string $phone
     * @param string $mask
     * @return string
     */
    public function formatPhone($phone = null, $mask = self::PHONE_MASK){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $ret = $phone;
        
        $size = strlen((string)$phone);
        
        
        if($size > 0){
            $ret = '';
            $d = 0;
            for($i=0; $i<strlen($mask); $i++){
                if($mask[$i] == 'd'){
                    if(isset($phone[$d])){                         
                        $ret .= $phone[$d]; 
                        $d++;
                    }
                }else{
                    $ret .= $mask[$i];
                }

            }
        }
        
        return $ret;
    }
    
    /**
     * 
     * @param string $phone
     * @return mixed
     */
    public function checkPhone($phone = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $phone = $this->cleanupPhone($phone);
        return $this->load('Validator')->validStringLength((string)$phone, 11, 11);
    }
    
    /**
     * @param string $mask
     * @return string
     */
    public function getPlaceholder($mask = self::PHONE_MASK){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        return str_replace('d', 'X', $mask);
    }
    
    /**
     * @return string
     */
    public function getPhoneMask(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        return self::PHONE_MASK;
    }
    
    /**
     * Get all mask
     * @return array
     */
    public function getMaskArray(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = array();
        
            $select = $this->select()
                           ->from(self::TABLE_PHONE_MASK)
                           ->columns(array(
                               'id',
                               'mask'
                           ))
                           ->order('id asc');
            
            $result = $this->fetchSelect($select);
            
            if($result){
                foreach($result as &$item){
                    $item['placeholder'] = $this->getPlaceholder($item['mask']);
                }
                
                $ret = $result;
            }

        return $ret; 
    }
}