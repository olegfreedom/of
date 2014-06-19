<?php
namespace Admin\Model;

class ForeignKeys extends \Application\Base\Model
{
    /**
     * Check foreign keys
     * @param string $table
     * @param int $val
     * @return bool
     */
    public function check($table, $val = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        
            if($val > 0){                
                $select = $this->select()
                               ->from(array('kcu' => 'information_schema.KEY_COLUMN_USAGE'))
                               ->columns(array(
                                   'table' => 'TABLE_NAME',
                                   'column' => 'COLUMN_NAME'
                               ))
                               ->where(array(
                                   'kcu.REFERENCED_TABLE_NAME' => $table,
                                   'kcu.REFERENCED_COLUMN_NAME' => 'id'
                               ));

                    $sqlStr = $select->getSqlString($this->adapter()->getPlatform());
                    $sqlStr = str_replace('information_schema.KEY_COLUMN_USAGE', 'information_schema`.`KEY_COLUMN_USAGE', $sqlStr); //hard fix

                $result = $this->sqlQuery($sqlStr);

                if($result){
                    $count = 0;

                    foreach($result as $item){
                        $select = $this->select()
                                       ->from($item['table'])
                                       ->columns(array('rows' => $this->expr('count(*)')))
                                       ->where(array($item['column'] => $val));

                        $rows = (int)$this->fetchOneSelect($select);
                        $count += $rows;
                    }

                    if($count > 0){
                        $ret = true;
                    }
                }
            }
        
        return $ret;
    }
}
