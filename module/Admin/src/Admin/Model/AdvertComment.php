<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 4/2/14
 * Time: 12:33 PM
 */

namespace Admin\Model;

class AdvertComment extends \Application\Base\Model
{
    static public $table = self::TABLE_ADVERTS_COMMENTS;
    static public $columns = array('id', 'parent_id', 'advert_id', 'comment_full', 'created');
    static public $order = 'created asc, parent_id asc';

    const POST_PER_PAGE = 20;


    /**
     * Get list
     * @param array $whereAr
     * @return array|null
     */
    public function get($whereAr = array(), $userId = 0){
        $this->log(get_called_class() . '\\' . __FUNCTION__);
        $this->checkStaticTable();

        $ret = null;

        $select = $this->select();
        $select->from(array('ac' => self::$table));

        $select->join(
            array('u' => self::TABLE_USER),
            'u.userid = ac.user_id',
            array()
        )->join(
            array('a' => self::TABLE_ADVERTS),
                'a.id = ac.advert_id',
                array()
        )->join(
            array('acr' => self::TABLE_ADVERTS_COMMENT_RATING),
            'acr.advert_comment_id = ac.id',
            array(),
            self::SQL_JOIN_LEFT
        )->addJoinColumns('u', array(
            'user_id' => 'userid',
            'user_username' => 'username',
        ))->addJoinColumns('a', array(
            // 'advert_id' => 'id',
            'advert_name' => 'name',
        ))->addJoinColumns('acr', array(
            'rating' => $this->expr('IF(SUM(acr.rating), SUM(acr.rating), 0)'),
            'is_voted' => $this->subQuery(
                $this->select()
                    ->from(array('acr_sub' => self::TABLE_ADVERTS_COMMENT_RATING))
                    ->columns(array('cnt_user_id' => $this->expr('IF(ac.user_id = ?, 1, COUNT(*))', array($userId))))
                    ->where(array(
                        'acr_sub.advert_comment_id' => $this->expr('ac.id'),
                        'acr_sub.user_id' => $userId
                    ))
                    ->limit(1)
            ),
        ))->group('ac.id');

        if ( !empty(self::$columns) )
        {
            $select->columns(self::$columns);
        }

        if ( !empty(self::$order) )
        {
            $select->order(self::$order);
        }

        if ( isset($whereAr ['page'])  )
        {
            if ( (int) $whereAr ['page'] > 0 )
            {
                $select->limitPage((int) $whereAr ['page'], self::POST_PER_PAGE);
            }
            unset($whereAr ['page']);
        }

        if ( !empty($whereAr) )
        {
            $select->where($whereAr);
        }

        $result = $this->fetchSelect($select);

        if($result){
            $ret = $result;
        }

        return $ret;
    }

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

    public function getCommentsByAdvertId($advert_id = 0, $userId = 0)
    {
        $tmpAr = $this->get(array('advert_id' => $advert_id), $userId);
        $resultAr = array();

        if ( !empty($tmpAr) )
        {
            foreach ( $tmpAr as $row )
            {
                if ( isset($row['parent_id']) )
                {
                    $resultAr [$row['parent_id']] [] = $row;
                }
                else
                {
                    $resultAr [0] [] = $row;
                }
            }

            $resultAr = $this->getChildren($resultAr, 0);
        }

        return $resultAr;
    }

    private function getChildren(&$rs, $parent_id)
    {
        $outAr = array();

        if ( isset($rs [$parent_id]) )
        {
            foreach ( $rs [$parent_id] as $row )
            {
                $childrenAr = $this->getChildren($rs, $row ['id']);
                if ( !empty($childrenAr) )
                {
                    $row ['children'] = $childrenAr;
                }
                $outAr [] = $row;
            }
        }

        return $outAr;
    }
}
