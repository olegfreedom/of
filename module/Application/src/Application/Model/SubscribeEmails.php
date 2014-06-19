<?php
namespace Application\Model;

class SubscribeEmails extends \Application\Base\Model
{
    const SUBSCRIBE_EMAILS_PER_PAGE = 50;

    /**
     * Get all list
     * @param int $page
     * @return null|array
     */
    public function getList($page = 0) {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->select()
                ->from(self::TABLE_SUBSCRIBE_EMAILS)
                ->columns(array(
                    'id',
                    'email',
                    'date'
                ))
                ->limitPage($page, self::SUBSCRIBE_EMAILS_PER_PAGE);

        $result = $this->fetchSelect($select);

        if ($result) {
            $ret = $result;
        }

        return $ret;
    }
    /**
     * Add email for subscribe
     * @param null $params
     * @return bool
     */
    public function add($params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($params != null && isset($params['email'])){
            $params['date'] = $this->load('Date', 'admin')->getDateTime();
            
            $insert = $this->insert(self::TABLE_SUBSCRIBE_EMAILS)
                           ->values($params);
            
            $result = $this->execute($insert);

            if($result){
                $ret = true;
            }
        }
        return $ret;
    }

    /**
     * Delete email
     * @param int $id
     * @return bool
     */
    public function remove($id = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($id > 0){
            $delete = $this->delete(self::TABLE_SUBSCRIBE_EMAILS)
                            ->where(array('id' => $id));
            $result = $this->execute($delete);

            if ($result) {
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * Check identical email
     * @param string $email
     * @return bool
     */
    public function checkEmail($email = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if (!empty($email)){
            $select = $this->select()
                           ->from(self::TABLE_SUBSCRIBE_EMAILS)
                           ->columns(array('id'))
                           ->where(array('email' => $email))
                           ->limit(1);

            $result = (int)$this->fetchOneSelect($select);
            
            if ($result > 0){
                $ret = true;
            }
        }

        return $ret;
    }

    /**
     * get paginator
     * @param int $page
     * @return null|array
     */
    public function getPaginator($page = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $count = 0;

        $select = $this->select()
                    ->from(self::TABLE_SUBSCRIBE_EMAILS)
                    ->columns(array(
                        'count' => $this->expr('count(*)')
                    ));

        $count = (int)$this->fetchOneSelect($select);

        return $this->paginator($page, $count, self::SUBSCRIBE_EMAILS_PER_PAGE);
    }
}
