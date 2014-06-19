<?php

namespace Admin\Model;

class Helps extends \Application\Base\Model
{

    /**
     * Get all list
     * @return null|array
     */
    public function getList() {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->select()
                ->from(self::TABLE_HELPS)
                ->columns(array(
                    'id',
                    'title',
                    'url'
                ));

        $result = $this->fetchSelect($select);

        if ($result) {
            $ret = $result;
        }

        return $ret;
    }

    /**
     * Get one
     * @param int $id
     * @return null|array
     */
    public function getOne($id = 0) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if ($id > 0) {
            $select = $this->select()
                    ->from(self::TABLE_HELPS)
                    ->columns(array(
                        'id',
                        'title',
                        'text'
                    ))
                    ->where(array('id' => $id))
                    ->limit(1);

            $result = $this->fetchRowSelect($select);

            if ($result) {
                $ret = $result;
            }
        }

        return $ret;
    }

    /**
     * Get text
     * @param string|array $url
     * @return null|string
     */
    public function getTextByUrl($url = null) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if ($url !== null) {
            $select = $this->select()
                    ->from(self::TABLE_HELPS)
                    ->columns(array('text'))
                    ->where(array('url' => $url));

            if(is_array($url)){
                $select->columns(array('url', 'text'));
                $result = $this->fetchSelect($select);

                if ($result) {
                    $ret = array();
                    foreach($result as $item){
                        $ret[$item['url']] = $item['text'];
                    }
                }
            }else{
                $select->limit(1);

                $result = $this->fetchOneSelect($select);

                if ($result) {
                    $ret = $result;
                }
            }

        }

        return $ret;
    }

    /**
     * Edit
     * @param int $id
     * @param array $params
     * @return bool
     */
    public function edit($id = 0, $params = null) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($id > 0 && $params !== null) {

            $update = $this->update(self::TABLE_HELPS)
                    ->set($params)
                    ->where(array('id' => $id));

            $ret = $this->execute($update);
        }

        return (bool) $ret;
    }

}