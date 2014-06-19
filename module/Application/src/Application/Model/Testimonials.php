<?php
namespace Application\Model;

class Testimonials extends \Application\Base\Model {
    
    /**
     * 
     * @param array $params
     * @return boolean
     */
    public function add($params = null){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = false;

        if($params !== null) {
            
            $advert_id = 0;
            
            if(isset($params['advert_id'])){
                $advert_id = $params['advert_id'];
                unset($params['advert_id']);
            }
            
            $params['timestamp'] = $this->load('Date', 'admin')->getDateTime();
            $insert = $this->insert(self::TABLE_TESTIMONIALS)
                           ->values($params);
            
            $ret = $this->execute($insert);
            
            
            $id = $this->insertId();
            
            if ((int)$advert_id > 0 && (int)$id > 0) {
                $param = array(
                    'testimonial_id' => $id,
                    'advert_id' => $advert_id
                );
 
                $this->addToLinkedTable($param);
            }
        }

        return (bool)$ret;
    }
    
    /**
     * Add testimonial to advert
     * @param array $params
     * @return boolean
     */
    public function addToLinkedTable($params = null) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
        if($params !== null) {
            $insert = $this->insert(self::TABLE_TESTIMONIALS_TO_ADVERT)
                           ->values($params);
            
            $ret = $this->execute($insert);
        }
        
        return (bool)$ret;
    }
}