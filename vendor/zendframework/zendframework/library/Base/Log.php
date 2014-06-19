<?php
namespace Base;

class Log
{
    private static $is_writable = true;
    private static $event_is_writable = true;

    public static function save($str){
        if(self::$is_writable == true){
            $filename = BASE_PATH.'/data/cache/project.log';
            if (is_writable($filename)) {
                $str = date('Y-m-d H:i:s') . "\t" . $_SERVER['REQUEST_URI'] . "\t" . $str . "\n";
                file_put_contents($filename, $str, FILE_APPEND);
            }else{
                self::$is_writable = false;
            }            
        }
    }

    public static function saveEvent($str){
        if ( !empty(self::$event_is_writable) )
        {
            $filename = BASE_PATH.'/data/cache/event.log';

            $str = date('Y-m-d H:i:s') . "\t" . $_SERVER['REQUEST_URI'] . "\t" . $str . "\n\n";

            $cnt = file_put_contents($filename, $str, FILE_APPEND);

            if ( $cnt === FALSE )
            {
                self::$event_is_writable = FALSE;
            }
        }
    }
}