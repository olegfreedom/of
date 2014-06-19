<?php

namespace Admin\Model;


class Pages extends \Application\Base\Model
{

    /**
     * Get all list
     * @return null|array
     */
    public function getList(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->select()
                       ->from(self::TABLE_PAGES)
                       ->columns(array(
                           'id',
                           'title',
                       ));

        $result = $this->fetchSelect($select);

        if($result){
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
                           ->from(self::TABLE_PAGES)
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

            $update = $this->update(self::TABLE_PAGES)
                           ->set($params)
                           ->where(array('id' => $id));

            $ret = $this->execute($update);
        }

        return (bool)$ret;
    }
}