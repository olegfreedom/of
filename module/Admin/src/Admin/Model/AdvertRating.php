<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 4/8/14
 * Time: 3:27 PM
 */

namespace Admin\Model;

class AdvertRating extends \Application\Base\Model
{
    static public $table = self::TABLE_ADVERTS_RATING;
    static public $columns = array('id', 'advert_id', 'user_id', 'rating', 'created');
    static public $order = 'created desc';

    /**
     * Add
     * @param array $params
     * @return bool
     */
    public function add($params = null){
        $this->log(get_called_class() . '\\' . __FUNCTION__);
        $this->checkStaticTable();

        $ret = false;

        if($params !== null){
            $params ['created'] = new \Zend\Db\Sql\Expression('NOW()');

            $insert = $this->insert(self::$table)
                ->values($params);

            $ret = $this->execute($insert);
        }

        return (bool)$ret;
    }
}
