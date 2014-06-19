<?php
namespace Admin\Model;

class RequireParams extends \Application\Base\Model
{
    /**
     * Get all list
     * @return null|array
     */
    public function getList(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->select()
            ->from(array('r' => self::TABLE_REQUIRE_PARAMS))
            ->columns(array(
                'category_id',
                'fields',
            ))
            ->join(
                array('c' => self::TABLE_ADVERTS_CATEGORIES),
                'c.id = r.category_id',
                array(
                    'category_name' => 'name',
                )
            );;

        $result = $this->fetchSelect($select);

        if($result){
            foreach ($result as &$item){

                $breadcrumbsArray = $this->load('AdvertCategory', 'admin')->getBreadcrumbsArray($item['category_id']);
                if(is_array($breadcrumbsArray)){
                    $item['breadcrumbs'] = $breadcrumbsArray;
                }
            }
            $ret = $result;
        }

        return $ret;
    }

    /**
     * Add
     * @param integer $id
     * @param array $params
     * @return boolean
     */
    public function add($id = 0, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if((int)$id > 0 && $params !== null){

            $params = $this->jsonEncode($params);
            $item = array(
                'fields' => $params
            );

            $select = $this->select()
                        ->from(self::TABLE_REQUIRE_PARAMS)
                        ->where(array('category_id' => $id));
            $result = $this->fetchOneSelect($select);

            if ($result){
                $insert = $this->update(self::TABLE_REQUIRE_PARAMS)
                    ->set($item)
                    ->where(array('category_id' => $id));
            } else {
                $item['category_id'] = $id;
                $insert = $this->insert(self::TABLE_REQUIRE_PARAMS)
                    ->values($item);
            }
            $ret = $this->execute($insert);
        }

        return (bool)$ret;
    }


    /**
     * Get one
     * @param integer $id
     * @return null|array
     */
    public function getOne($id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $parentArray = $this->load('AdvertCategory', 'admin')->getParentsArray($id);

        if($id > 0){
            $select = $this->select()
                ->from(self::TABLE_REQUIRE_PARAMS)
                ->where(array('category_id' => $parentArray))
                ->order('category_id desc')
                ->limit(1);
            $result = $this->fetchRowSelect($select);

            if($result){
                $ret = $result;
                $ret['fields'] = $this->jsonDecode($ret['fields']);
            }
        }

        return $ret;
    }

    /**
     * Remove option
     * @param $id
     * @return bool
     */
    public function remove($id = array())
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($id > 0) {
            $delete = $this->delete(self::TABLE_REQUIRE_PARAMS)
                ->where(array('category_id' => $id));
            $ret = (bool)$this->execute($delete);
        }
        return $ret;
    }

}