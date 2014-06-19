<?php
namespace Admin\Model;

use \Base\Filter\ImageResize;

class Upload extends \Application\Base\Model
{ 
    private $imgType = array('png', 'jpg', 'jpeg');
    
    /**
     * Upload file
     * @param mixed $file
     * @param null|array $type
     * @param null|string $cat
     * @param array $params
     * @param bool $crop
     * @return null|string
     */
    public function save($file = null, $type = null, $cat = null, $params = array('width' => 1024, 'height' => 768), $crop = false){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        if($file !== null && isset($file['error']) && $file['error'] == 0){
            $file_name = strtolower($file['name']);
            $file_type = explode('/', $file['type']);
            $file_type = end($file_type);

            if((is_array($type) && in_array($file_type, $type)) || $type === null){
                $file_name = $this->upload($file_name, $file['tmp_name'], $cat);

                if($file_name !== null){
                    if(in_array($file_type, $this->imgType)){
                        $params['method'] = ($crop === true) ? ImageResize::METHOD_SCALE_MIN : ImageResize::METHOD_SCALE_MAX;
                        $this->resize($file_name, $params);
                    }
                    
                    $ret = '/upload/'.$file_name;
                }
            }
        }
        
        return $ret;
    }
    
    /**
     * Upload file
     * @param string $file_name
     * @param mixed $tmp
     * @param null|string $cat
     * @return null|string
     */
    private function upload($file_name, $tmp, $cat = null){
        $ret = null;

        if($tmp && $file_name){
            $dir = ($cat !== null) ? $cat.'/' : '';            
            $exp = explode('.', $file_name);
            $type = end($exp);
            unset($exp[count($exp)-1]);
            $name = implode('.', $exp);
            $sufix = '';

            if(!is_dir($this->dir().$dir)){
                mkdir($this->dir().$dir, 0777, true);
            }

            $i = 1;
            while(is_file($this->dir().$dir.$name.$sufix.'.'.$type)){
                $sufix = $i++;
            }
            
            $file_name = $dir.$name.$sufix.'.'.$type;
            
            if(move_uploaded_file($tmp, $this->dir().$file_name)){
                $ret = $file_name;
            }
        }
        
        return $ret;
    }
    
    /**
     * resize image
     * @param string $file_name
     * @param array $params
     */
    private function resize($file_name, $params){
        $resize = new ImageResize($params);
        $resize->filter($this->dir().$file_name);
    }
    
    /**
     * Upload dir
     * @return string
     */
    private function dir(){
        return PUBLIC_PATH.'/upload/';
    }
    
    /**
     * Unlink file
     * @param string $url
     * @return boolean
     */
    public function unlink($url){
        $ret = false;
        
        if(is_file(PUBLIC_PATH.$url)){
            @unlink(PUBLIC_PATH.$url);
            $ret = true;
        } 
        
        return $ret;
    }
}