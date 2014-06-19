<?php
namespace Base\Sql;

class CustomSelect extends \Zend\Db\Sql\Select
{  
    /**
     * Add join columns
     * 
     * @param string $name
     * @param string|array $columns
     * @param bool $reset Reset columns
     * @throws \Zend\Db\Sql\Exception\InvalidArgumentException
     * @return \Zend\Db\Sql\Select
     */
    public function addJoinColumns($name, $columns = self::SQL_STAR, $reset = false) {
        if (!is_string($name)) {
            throw new \Zend\Db\Sql\Exception\InvalidArgumentException(
                    "addJoinColumns() expects 'name' as an string"
            );
        }
        if (!is_array($columns)) {
            $columns = array($columns);
        }
        
        foreach($this->joins as &$item){
            if(is_array($item['name'])){
                $joinName = key($item['name']);
            }else{
                $joinName = $item['name'];
            }
            
            if($joinName == $name){
                if($reset == true){
                    $item['columns'] = $columns;           
                }else{
                    $item['columns'] = array_merge($item['columns'], $columns);
                }
                break;
            } 
        }

        return $this;
    }
    

    /**
     * Sets the limit and count by page number.
     *
     * @param int $page Limit results to this page number.
     * @param int $rowCount Use this many rows per page.
     * @return \Zend\Db\Sql\Select
     */
    public function limitPage($page = 0, $rowCount = 10)
    {
        $page = ($page > 0) ? $page : 1;
        $rowCount = ($rowCount > 0) ? $rowCount : 1;
        
        $this->limit( (int)$rowCount )
             ->offset( (int)($rowCount * ($page - 1)) );
        
        return $this;
    }
}