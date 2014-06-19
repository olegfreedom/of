<?php
namespace Admin\Model;

class SearchLog extends \Application\Base\Model
{
    const POST_PER_PAGE = 20;

    /**
     * Get List of Search Log
     * @param int $page
     * @return array|null
     */
    public function getList($page = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = null;

        $select = $this->select()
                       ->from(self::TABLE_SEARCH_LOG)
                       ->columns(array(
                           'id',
                           'text',
                           'count',
                           'date' => $this->expr('date_format(timestamp, "%d.%m.%Y %H:%i")')
                       ))
                       ->order('count desc')
                       ->order('timestamp desc');

       if((int)$page > 0){
           $select->limitPage($page, self::POST_PER_PAGE);
       }

        $result = $this->fetchSelect($select);

        if ($result){
            $ret = $result;
        }

        return $ret;
    }

    /**
     * Add item to log
     * @param string $text
     * @return bool
     */
    public function add($text = ''){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        $text = (string)$text;
        
        if (!empty($text)){
            $select = $this->select()
                           ->from(self::TABLE_SEARCH_LOG)
                           ->columns(array('id'))
                           ->where(array('text' => $text))
                           ->limit(1);
            
            $id = (int)$this->fetchOneSelect($select);

            if ($id > 0) {
                $update = $this->update(self::TABLE_SEARCH_LOG)
                               ->set(array(
                                    'count' => $this->expr('count + 1'),
                                    'timestamp' => $this->load('Date', 'admin')->getDateTime()
                               ))
                               ->where(array('id' => $id));

                $ret = $this->execute($update);
            } else {
                $insert = $this->insert(self::TABLE_SEARCH_LOG)
                               ->values(array(
                                   'text' => $text,
                                   'timestamp' => $this->load('Date', 'admin')->getDateTime()
                               ));

                $ret = $this->execute($insert);
            }
        }

        return (bool)$ret;
    }

    /**
     * get paginator
     * @param int $page
     * @return null|array
     */
    public function getPaginator($page = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $page = ((int)$page > 0) ? $page : 1;
        $count = 0;

        $select = $this->select()
                       ->from(self::TABLE_SEARCH_LOG)
                       ->columns(array(
                           'count' => $this->expr('count(id)')
                       ));

        if($select){
            $count = (int)$this->fetchOneSelect($select);
        }

        return $this->paginator($page, $count, self::POST_PER_PAGE);
    }
}