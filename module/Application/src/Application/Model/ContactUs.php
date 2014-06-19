<?php
namespace Application\Model;

class ContactUs extends \Application\Base\Model {
    
    /**
     * Add comment
     * @param array $params
     * @return boolean
     */
    public function add($params){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = false;
        
        if($params !== null) {
            $params['timestamp'] = $this->load('Date', 'admin')->getDateTime();
            $insert = $this->insert(self::TABLE_CONTACT_US)
                           ->values($params);
            
            $ret = $this->execute($insert);
        }

        return (bool)$ret;
    }
    
}

