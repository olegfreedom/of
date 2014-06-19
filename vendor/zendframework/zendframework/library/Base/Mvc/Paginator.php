<?php
namespace Base\Mvc;

class Paginator
{
    /**
     * Page paginator
     * @param int $page Current page
     * @param int $count All rows
     * @param int $rows Rows per page
     * @return null|array
     */
    public function __invoke($page = 0, $count = 0, $rows = 10){
        $ret = null;
        $page = (int)$page;
        $count = (int)$count;
        $rows = (int)$rows;
        
        if($page > 0 && $count > 0 && $rows > 0 && $count > $rows){
            $limit = 2;
            
            $ret = array();
            
            $size = ceil($count / $rows);
            $min = (($page - $limit) > 0) ? ($page - $limit) : 1;
            $max = (($page + $limit) < $size) ? ($page + $limit) : $size;
            
            // set prev page
            if($page > 1){ 
                $ret[] = array(
                    'val' => ($page-1),
                    'type' => 'prev'
                );
            }
            
            // set first page
            if($page-$limit > 1){
                $ret[] = array(
                    'val' => 1,
                    'type' => 'page'
                );
                
                if($page-$limit-1 > 1){
                    $ret[] = array(
                        'val' => '...',
                        'type' => 'empty'
                    );
                }
            }
            
            // set pages
            for($i=$min; $i<=$max; $i++){
                $ret[] = array(
                    'val' => $i,
                    'type' => ($page == $i) ? 'current' : 'page'
                );
            }
            
            // set last page
            if($page+$limit < $size){
                if($page+$limit+1 < $size){
                    $ret[] = array(
                        'val' => '...',
                        'type' => 'empty'
                    );
                }
                $ret[] = array(
                    'val' => $size,
                    'type' => 'page'
                );
            }
            
            // set next page
            if($page < $size){
                $ret[] = array(
                    'val' => ($page+1),
                    'type' => 'next'
                );
            }
        }
        
        return $ret;
    }
}