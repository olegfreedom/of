<?php
namespace Profile\Model;

class Favorites extends \Application\Base\Model
{
    /**
     * Add
     * @param int $advert
     * @param int $user_id
     * @return bool
     */

    public function add($advert = 0, $user_id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = 0;

        if ($advert > 0 && $user_id > 0) {

            if ($advert > 0) {
                $params = array(
                    'user_id' => $user_id,
                    'advert_id' => $advert
                );

                $insert = $this->insert(self::TABLE_FAVORITES)
                    ->values($params);

                $this->execute($insert);
                $id = $this->insertId();

                if ($id > 0) {
                    $ret = $id;
                }
            }
        }
        return $ret;
    }

    /**
     * Remove
     * @param int $id
     * @param int $user_id
     * @return bool
     */
    public function remove($id = 0, $user_id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($id > 0 && $user_id > 0) {
            $delete = $this->delete(self::TABLE_FAVORITES)
                ->where(array(
                    'id' => $id,
                    'user_id' => $user_id
                ));

            $ret = $this->execute($delete);
        }

        return (bool)$ret;
    }

}