<?php
namespace Admin\Model;

class Banners extends \Application\Base\Model
{
    /**
     * Get lost of banners
     * @return array|bool
     */
    public function getList()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        $select = $this->select()
                       ->from(self::TABLE_BANNERS)
                       ->columns(array(
                            'id',
                            'title',
                            'height',
                            'url',
                            'image'
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
    public function getOne($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        if($id > 0){
            $select = $this->select()
                ->from(self::TABLE_BANNERS)
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
     * Add
     * @param array $params
     * @param mixed $img
     * @param int $height
     * @return bool
     */
    public function add($params = null, $img = null, $height = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($params !== null){
            $photo = $this->load('Upload', 'admin')->save($img, array('gif', 'png', 'jpg', 'jpeg'), 'banners', array('width' => 200, 'height' => $height), true);

            if($photo !== null){
                $params['image'] = $photo;
                $params['height'] = $height;
            }

            $insert = $this->insert(self::TABLE_BANNERS)
                ->values($params);

            $ret = $this->execute($insert);
        }

        return (bool)$ret;
    }

    /**
     * Edit
     * @param int $id
     * @param array $params
     * @param mixed $img
     * @param int $height
     * @return bool
     */
    public function edit($id = 0, $params = null, $img = null, $height = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0 && $params !== null){
            $photo = $this->load('Upload', 'admin')->save($img, array('gif', 'png', 'jpg', 'jpeg'), 'banners', array('width' => 200, 'height' => $height), true);

            if($photo !== null){
                $params['image'] = $photo;
                $params['height'] = $height;

                $post = $this->getOne($id);
                if(isset($post['image'])){
                    $this->load('Upload', 'admin')->unlink($post['image']);
                }
            }

            $update = $this->update(self::TABLE_BANNERS)
                ->set($params)
                ->where(array('id' => $id));

            $ret = $this->execute($update);
        }

        return (bool)$ret;
    }

    /**
     * Remove
     * @param int $id
     * @return bool
     */
    public function remove($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if($id > 0){
            $post = $this->getOne($id);

            $delete = $this->delete(self::TABLE_BANNERS)
                ->where(array('id' => $id));

            $ret = $this->execute($delete);

            if($ret && isset($post['img'])){
                $this->load('Upload', 'admin')->unlink($post['image']);
            }
        }

        return (bool)$ret;
    }
}