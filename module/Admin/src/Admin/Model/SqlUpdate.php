<?php
namespace Admin\Model;

class SqlUpdate extends \Application\Base\Model
{
    private function createTable(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $sql = 'CREATE TABLE IF NOT EXISTS `'.self::TABLE_SQL_UPDATES.'` (
                `id` int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `file` varchar(255) NOT NULL
               ) COMMENT="" ENGINE="InnoDB" COLLATE "utf8_general_ci";';
        
        $this->sqlQuery($sql);
    }
    
    /**
     * Get all list
     * @return null|array
     */
    public function getList(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        $this->createTable();

        $select = $this->select()
                       ->from(self::TABLE_SQL_UPDATES)
                       ->columns(array('file'))
                       ->order('id desc');

        $result = $this->fetchColSelect($select);

        if($result){               
           $ret = $result; 
        }

        return $ret;
    }   
    
    /**
     * Set Update
     * @param string $type
     * @param array $files
     * @return bool
     */
    public function setUpdate($type = 'update', $files = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = false;
        $this->createTable();

        foreach($files as $item){
            if($type == 'update'){
                $sql_str='';
                $file=file(BASE_PATH.'/_sql/'.$item);

                foreach ($file as $line){
                    $line=trim($line);
                    if(!empty($line)){
                       $sql_str .= $line."\n"; 
                    }
                }

                if($sql_str){
                    $exp = explode(";\n", $sql_str);
                    foreach($exp as $query){
                        if(trim($query)!=''){
                            $this->sqlQuery($query);
                        }
                    }
                    $ret = true;
                }
            }

            $insert = $this->insert(self::TABLE_SQL_UPDATES)
                           ->values(array('file' => $item));

            $this->execute($insert);
        }
       
       return $ret;
    }
    
    /**
     * Scan sql dir
     * @return array
     */
    public function scanDir(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = array();
        $dir = scandir(BASE_PATH.'/_sql/');
        $list = (array)$this->getList();

        foreach($dir as $item){
            if(!in_array($item, $list)){                
                if(preg_match('/^update-([0-9]{4})-([0-9]{2})-([0-9]{2})\#([0-9]+)\.sql$/i', $item)){
                    $ret[] = $item;
                }
            }
        }
        
        return $ret;
    }
    
}