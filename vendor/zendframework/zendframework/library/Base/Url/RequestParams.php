<?php
namespace Base\Url;

class RequestParams{
    
    /**
     * 
     * @param str $param_str
     * @return array
     */
    public static function explode($param_str = ''){
        $getParams = array();
        
        if(!empty($param_str)){
            $request = explode('/', $param_str);
            
            $size = sizeof($request);

            if($size > 0){
                for($i = 0; $i < $size; $i++){
                    $key = $request[$i++];
                    $val = isset($request[$i]) ? $request[$i] : '';

                    if(!empty($key)){
                        $getParams[$key] = $val;
                    }
                }
            }
        }
        
        return $getParams;
    }
}