<?php
namespace Application\Model;

class AdvertCounter extends \Application\Base\Model
{    
    /**
     * update counter
     * @param int $id
     * @return boolean
     */
    public function up($id = 0){
        $this->log(__CLASS__.'\\'.__FUNCTION__);   
        
        $ret = false;
            
        if((int)$id > 0){
            $update = $this->update(self::TABLE_ADVERTS)
                           ->set(array(
                               'counter' => $this->expr('(counter+1)')
                           ))
                           ->where(array('id' => $id));

            $result = $this->execute($update);

            if($result){
                $ret = true;
            }
        }
        
        return $ret;
    }
}

?>
