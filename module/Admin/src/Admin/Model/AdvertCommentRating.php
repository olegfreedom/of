<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 4/7/14
 * Time: 10:49 AM
 */

namespace Admin\Model;

class AdvertCommentRating extends \Application\Base\Model
{
    static public $table = self::TABLE_ADVERTS_COMMENT_RATING;
    static public $columns = array('id', 'advert_comment_id', 'user_id', 'rating', 'created');
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


/*
    public function getRatingByAdvertCommentId($advert_comment_id = 0)
    {
        $select = $this->select()
            ->from(array('a' => self::$table))
            ->columns(array(
                'total_rating' => $this->expr('SUM(rating)')
            ))
            ->where(array('advert_comment_id' => $advert_comment_id))
            ->limit(1);

        $result = $this->fetchRowSelect($select);

        return $result;
    }
*/
}
