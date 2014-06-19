<?php
namespace Application\Model;

use \Base\Filter\ImageResize;

class Image extends \Application\Base\Model
{
    const WATERMARK_IMG = 'watermark.png';
    const HSHIFT = 5;
    const VSHIFT = 5;

    /**
     * Render image from url
     * @param string $url
     * @param int $w
     * @param int $h
     * @param string $crop "y" or "n"
     */
    public function get($url = null, $w = 0, $h = 0, $crop = 'n'){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        if($url !== null && !empty($url)){
            // image path
            $image_path = PUBLIC_PATH.$url;
            $watermark_path = PUBLIC_PATH.'/img/'.self::WATERMARK_IMG;

            $flagWatermark = FALSE;
            if ( is_file($watermark_path) )
            {
                $flagWatermark = TRUE;
                $watermark_time = filemtime($watermark_path);
            }

            if ( is_file($image_path) ){
                // image time
                $image_time = filemtime($image_path);

                // determine image format
                list($width, $height, $type) = getimagesize($image_path); 
                $content_type = image_type_to_mime_type($type);

                $cacheHash = md5($url);
                $cacheDir = $this->getCacheDir($cacheHash);
                $cacheImage = $cacheHash.'.'.$this->getFileType($url);
                if(!is_dir($cacheDir)){
                    @mkdir($cacheDir, 0777, true);
                }

                // check image from cache
                if(
                   is_file($cacheDir.$cacheImage) && 
                   is_readable($cacheDir.$cacheImage) && 
                   filemtime($cacheDir.$cacheImage) > $image_time && 
                   (!$flagWatermark || (filemtime($cacheDir.$cacheImage) > $watermark_time))
                  ){
                    $image = file_get_contents($cacheDir.$cacheImage);
                }else{
                    $image = $this->render($image_path, $width, $height, $type, $flagWatermark ? $watermark_path : FALSE);
                    file_put_contents($cacheDir.$cacheImage, $image);
                }

                if((int)$w > 0 && (int)$h > 0 && in_array($crop, array('y', 'n'))){
                    // small image
                    $copyImage = $cacheDir.$cacheHash.'_'.$w.'_'.$h.(($crop=='y') ? '_crop' : '').'.'.$this->getFileType($url);
                    if(
                       is_file($copyImage) && 
                       is_readable($copyImage) && 
                       filemtime($copyImage) > $image_time &&
                       (!$flagWatermark || filemtime($copyImage) > $watermark_time)
                      ){
                        $image = file_get_contents($copyImage);
                    }else{
                        if(copy($cacheDir.$cacheImage, $copyImage)){
                            $method = ($crop == 'y') ? ImageResize::METHOD_SCALE_MIN : ImageResize::METHOD_SCALE_MAX; 
                            $resize = new ImageResize(array('width' => $w, 'height' => $h, 'method' => $method));
                            $resize->filter($copyImage);
                            $image = file_get_contents($copyImage);
                        }
                    }
                }
                
                // this tells the browser to render image
                header('content-type: '.$content_type); 
                header("Cache-Control: public, max-age=3600");
                header("Expires: ".gmdate('D, d M Y H:i:s T', time() + 3600));
                header("Pragma:public");
                header("Last-Modified: ".date('r'));

                echo($image);
            }
        }
        die();
    }
    
    /**
     * 
     * @param string $image_path
     * @param int $width
     * @param int $height
     * @param int $type
     * @param string $watermark_path
     * @return mixed
     * @throws \Exception
     */
    private function render($image_path, $width, $height, $type, $watermark_path){
        $this->log(__CLASS__.'\\'.__FUNCTION__);


        $image = $this->imageCreate($image_path, $type);
        //something went wrong
        if ($image === false) {
            return false;
        }

        imagealphablending($image, true);

        if ( !empty($watermark_path) )
        {
            // creating png image of watermark
            $watermark = imagecreatefrompng($watermark_path);

            // getting dimensions of watermark image
            $watermark_width = imagesx($watermark);
            $watermark_height = imagesy($watermark);

            // placing the watermark 5px from bottom and right
            $dest_x = $width - $watermark_width - self::HSHIFT;
            $dest_y = $height - $watermark_height - self::VSHIFT;

            // blending the images together
            imagealphablending($watermark, true);

            if($watermark_width < $width && $watermark_height < $height){
                // creating the new image
                imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
            }

            imagedestroy($watermark);
        }
        else
        {
            $src_width = imagesx($image);
            $src_height = imagesy($image);
            imagecopy($image, $image, $width, $height, 0, 0, $src_width, $src_height);
        }

        // Define outputing function
        ob_start();
        switch ($type)
        {
            case IMAGETYPE_GIF:
                imagegif($image);
                break;
            case IMAGETYPE_JPEG:
                imagejpeg($image, null, 100); // best quality
                break;
            case IMAGETYPE_PNG:
                imagepng($image, null, 0); // no compression
                break;
            default:
                ob_end_clean();
                throw new \Exception('Unknown image type');
        }
        
        // destroying and freeing memory
        imagedestroy($image);  

        $finalImage = ob_get_clean();
        
        return $finalImage;          
    }
    
    /**
     * 
     * @param string $image_path
     * @param int $type
     * @return bool|mixed
     * @throws \Exception
     */
    private function imageCreate($image_path, $type){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        if (!is_file($image_path) || !is_readable($image_path)) {
           throw new \Exception('Unable to open file');
           return false;
        }

        switch ($type) {
            case IMAGETYPE_GIF:
                return imagecreatefromgif($image_path);
                break;

            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($image_path);
                break;

            case IMAGETYPE_PNG:
                return imagecreatefrompng($image_path);
                break;
        }
        
        throw new \Exception('Unsupport image type');
        return false;
    }
    
    /**
     * 
     * @param string $cacheHash
     * @return string
     */
    private function getCacheDir($cacheHash){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $dir = BASE_PATH.'/data/cache/image/';
        return $dir.substr($cacheHash, 0, 4).'/'.substr($cacheHash, 4, 4).'/';
    }
    
    /**
     * 
     * @param string $url
     * @return string
     */
    private function getFileType($url){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $exp = explode('.', $url);
        return end($exp);
    }
}