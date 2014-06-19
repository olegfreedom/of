<?php
namespace Admin\Model;

class Faq extends \Application\Base\Model
{
    /**
     * Get all list
     * @param integer $page
     * @return null|array
     */
    public function getList($page = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;

        $select = $this->select()
                       ->from(self::TABLE_FAQ)
                       ->columns(array(
                           'id',
                           'title',
                           'content'
                       ));
        
        if($page > 0){
            $select->limitPage($page);
        }

        $result = $this->fetchSelect($select);

        if($result){ 
           $i = 1;
           foreach($result as &$item){
               $item['number'] = $i;
               $item['position'] = (($i%2 > 0) ? 'left' : 'right');
               $item['typical'] = ($i > 1 ? (($i%2 > 0) ? 3 : 2) : 1);
               
               $i++;
           }
            
           $ret = $result; 
        }

        return $ret;
    }
    
    /**
     * Get one
     * @param integer $id
     * @return null|array
     */
    public function getOne($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if($id > 0){
            $select = $this->select()
                           ->from(self::TABLE_FAQ)
                           ->columns(array(
                               'id',
                               'title',
                               'content'
                           ))
                           ->where(array('id' => $id))
                           ->limit(1);

            $result = $this->fetchRowSelect($select);

            if($result){
                $ret = $result;
            }
        }

        return $ret;
    }
    
    /**
     * Edit
     * @param integer $id
     * @param array $params
     * @return boolean
     */
    public function edit($id = 0, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if((int)$id > 0 && $params !== null){

            $update = $this->update(self::TABLE_FAQ)
                           ->set($params)
                           ->where(array('id' => $id));

            $ret = $this->execute($update);
        }

        return (bool)$ret;
    }
}