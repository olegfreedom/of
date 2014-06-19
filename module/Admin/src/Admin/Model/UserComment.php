<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 4/28/14
 * Time: 4:57 PM
 */

namespace Admin\Model;

class UserComment extends \Application\Base\Model
{
    static public $table = self::TABLE_USERS_COMMENTS;
    static public $columns = array('id', 'parent_id', 'user_id', 'user_commented_id', 'comment_full', 'created');
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
        $select->from(array('uc' => self::$table));

        $select->join(
                array('u' => self::TABLE_USER),
                'u.id = uc.user_id',
                array()
            )->join(
                array('u2' => self::TABLE_USER),
                'u2.id = uc.user_commented_id',
                array()
            )->join(
                array('ucr' => self::TABLE_USERS_COMMENT_RATING),
                'ucr.user_comment_id = uc.id',
                array(),
                self::SQL_JOIN_LEFT
            )->addJoinColumns('u', array(
                'user_id' => 'id',
                'user_secondname' => 'secondname',
                'user_firstname' => 'firstname',
                'user_lastname' => 'lastname',
                'user_username' => 'username',
            ))->addJoinColumns('u2', array(
                'user_commented_id' => 'id',
                'user_commented_secondname' => 'secondname',
                'user_commented_firstname' => 'firstname',
                'user_commented_lastname' => 'lastname',
            ))->addJoinColumns('ucr', array(
                'rating' => $this->expr('IF(SUM(ucr.rating), SUM(ucr.rating), 0)'),
                'is_voted' => $this->subQuery(
                        $this->select()
                            ->from(array('ucr_sub' => self::TABLE_USERS_COMMENT_RATING))
                            ->columns(array('cnt_user_id' => $this->expr('IF(uc.user_id = ?, 1, COUNT(*))', array($userId))))
                            ->where(array(
                                'ucr_sub.user_comment_id' => $this->expr('uc.id'),
                                'ucr_sub.user_id' => $userId
                            ))
                            ->limit(1)
                    ),
            ))->group('uc.id');

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

    public function getCommentsByUserId($user_get_id = 0, $userId = 0)
    {
        $tmpAr = $this->get(array('uc.user_id' => $user_get_id), $userId);
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
