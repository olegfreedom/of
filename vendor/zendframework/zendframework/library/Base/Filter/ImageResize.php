<?php
namespace Base\Filter;

use Zend\Filter\AbstractFilter;

class ImageResize extends AbstractFilter
{
    /**
     * Maximal scaling
     */
    const METHOD_SCALE_MAX = 0;
    /**
     * Minimal scaling
     */
    const METHOD_SCALE_MIN = 1;
    /**
     * Cropping of fragment
     */
    const METHOD_CROP      = 2;

    /**
     * Align center
     */
    const ALIGN_CENTER = 0;
    /**
     * Align left
     */
    const ALIGN_LEFT   = -1;
    /**
     * Align right
     */
    const ALIGN_RIGHT  = +1;
    /**
     * Align top
     */
    const ALIGN_TOP    = -1;
    /**
     * Align bottom
     */
    const ALIGN_BOTTOM = +1;
    
    /**
     * @var array Default options
     * @see setOptions
     */
    protected $options = array(
        'width'   => 1024,                    // Width of image
        'height'  => 768,                    // Height of image
        'method'  => self::METHOD_SCALE_MAX, // Method of image creating
        'percent' => 0,                      // Size of image per size of original image
        'halign'  => self::ALIGN_CENTER,     // Horizontal align
        'valign'  => self::ALIGN_CENTER,     // Vertical align
    );
    
    /**
     * Constructor
     * 
     * @param array $options Filter options
     */
    public function __construct($options = array()) {
        $this->setOptions($options);
    }
    
    /**
     * Set options
     *
     * @param array|Zend_Config $options Thumbnail options
     *         <pre>
     *         width   int    Width of image
     *         height  int    Height of image
     *         percent number Size of image per size of original image
     *         method  int    Method of image creating
     *         halign  int    Horizontal align
     *         valign  int    Vertical align
     *         </pre>
     * @return object
     */
    public function setOptions($options)
    {        
        foreach ($options as $k => $v) {
            if (array_key_exists($k, $this->options)) {
                $this->options[$k] = $v;
            }
        }
        return $this;
    }
    
    /**
     * Resize the file $value with the defined settings
     *
     * @param  string $value Full path of file to change
     * @return string The filename which has been set, or false when there were errors
     */
    public function filter($value)
    {
        if (!file_exists($value)) {
            throw new \Exception("File '$value' not found");
        }

        if (file_exists($value) and !is_writable($value)) {
            throw new \Exception("File '$value' is not writable");
        }

        $content = file_get_contents($value);
        if (!$content) {
            throw new \Exception("Problem while reading file '$value'");
        }

        $resized = $this->resize($content, $value);
        $result  = file_put_contents($value, $resized);

        if (!$result) {
            throw new \Exception("Problem while writing file '$value'");
        }

        return $value;
    }
    
    /**
     * Resize image
     *
     * @param $content Content of source imge
     * @param $value   Path to source file
     * @return Content of resized image
     */
    protected function resize($content, $value)
    {
        $sourceImage = ImageCreateFromString($content);
        if (!is_resource($sourceImage)) {
            throw new \Exception("Can't create image from given file");
        }
        
        $sourceWidth  = ImageSx($sourceImage);
        $sourceHeight = ImageSy($sourceImage);
        
        if ($sourceWidth <= $this->options['width'] && $sourceHeight <= $this->options['height']) {
            ImageDestroy($sourceImage);
            return $content;
        }
        
        list( , , $imageType) = GetImageSize($value);
        
        switch ($this->options['method']) {
            case self::METHOD_CROP:
                list($X, $Y, $W, $H, $width, $height) = $this->__calculateCropCoord($sourceWidth, $sourceHeight);
                break;
            case self::METHOD_SCALE_MAX:
                list($X, $Y, $W, $H, $width, $height) = $this->__calculateScaleMaxCoord($sourceWidth, $sourceHeight);
                break;
            case self::METHOD_SCALE_MIN:
                list($X, $Y, $W, $H, $width, $height) = $this->__calculateScaleMinCoord($sourceWidth, $sourceHeight);
                break;
            default:
                throw new \Exception('Unknow resize method');
        }
        
        // Create the target image
        if (function_exists('imagecreatetruecolor')) {
            $targetImage = ImageCreateTrueColor($width, $height);
        } else {
            $targetImage = ImageCreate($width, $height);
        }
        if (!is_resource($targetImage)) {
            throw new \Exception('Cannot initialize new GD image stream');
        }
        
        // Copy the source image to the target image
        if ($this->options['method'] == self::METHOD_CROP) {
            $result = ImageCopy($targetImage, $sourceImage, 0, 0, $X, $Y, $W, $H);
        } elseif (function_exists('imagecopyresampled')) {
            $result = ImageCopyResampled($targetImage, $sourceImage, 0, 0, $X, $Y, $width, $height, $W, $H);
        } else {
            $result = ImageCopyResized($targetImage, $sourceImage, 0, 0, $X, $Y, $width, $height, $W, $H);
        }
        ImageDestroy($sourceImage);
        if (!$result) {
            throw new \Exception('Cannot resize image');
        }
        
        ob_start();
        switch ($imageType)
        {
            case IMAGETYPE_GIF:
                ImageGif($targetImage);
                break;
            case IMAGETYPE_JPEG:
                ImageJpeg($targetImage, null, 100); // best quality
                break;
            case IMAGETYPE_PNG:
                ImagePng($targetImage, null, 0); // no compression
                break;
            default:
                ob_end_clean();
                throw new \Exception('Unknow resize method');
        }
        ImageDestroy($targetImage);
        $finalImage = ob_get_clean();
        
        return $finalImage;
    }
    
    /**
     * Calculate coordinates for crop method
     *
     * @param int $sourceWidth  Width of source image
     * @param int $sourceHeight Height of source image
     * @return array
     */
    private function __calculateCropCoord($sourceWidth, $sourceHeight)
    {
        if ( $this->options['percent'] ) {
            $W = floor($this->options['percent'] * $sourceWidth);
            $H = floor($this->options['percent'] * $sourceHeight);
        } else {
            $W = $this->options['width'];
            $H = $this->options['height'];
        }
        
        $X = $this->__coord($this->options['halign'], $sourceWidth,  $W);
        $Y = $this->__coord($this->options['valign'], $sourceHeight, $H);
        
        return array($X, $Y, $W, $H, $W, $H);
    }

    /**
     * Calculate coordinates for Max scale method
     *
     * @param int $sourceWidth  Width of source image
     * @param int $sourceHeight Height of source image
     * @return array
     */
    private function __calculateScaleMaxCoord($sourceWidth, $sourceHeight)
    {
        if ( $this->options['percent'] ) {
            $width  = floor($this->options['percent'] * $sourceWidth);
            $height = floor($this->options['percent'] * $sourceHeight);
        } else {
            $width  = $this->options['width'];
            $height = $this->options['height'];
            
            if ( $sourceHeight > $sourceWidth ) {
                $width  = floor($height / $sourceHeight * $sourceWidth);
            } else {
                $height = floor($width / $sourceWidth * $sourceHeight);
            }
        }
        return array(0, 0, $sourceWidth, $sourceHeight, $width, $height);
    }
    
    /**
     * Calculate coordinates for Min scale method
     *
     * @param int $sourceWidth  Width of source image
     * @param int $sourceHeight Height of source image
     * @return array
     */
    private function __calculateScaleMinCoord($sourceWidth, $sourceHeight)
    {
        $X = $Y = 0;
        
        $W = $sourceWidth;
        $H = $sourceHeight;
        
        if ( $this->options['percent'] ) {
            $width  = floor($this->options['percent'] * $W);
            $height = floor($this->options['percent'] * $H);
        } else {
            $width  = $this->options['width'];
            $height = $this->options['height'];
            
            $Ww = $W / $width;
            $Hh = $H / $height;
            if ( $Ww > $Hh ) {
                $W = floor($width * $Hh);
                $X = $this->__coord($this->options['halign'], $sourceWidth, $W);
            } else {
                $H = floor($height * $Ww);
                $Y = $this->__coord($this->options['valign'], $sourceHeight, $H);
            }
        }
        return array($X, $Y, $W, $H, $width, $height);
    }
    
    /**
     * Calculation of the coordinates
     *
     * @param int $align Align type
     * @param int $src   Source size
     * @param int $dst   Destination size
     * @return int
     */
    private function __coord($align, $src, $dst)
    {
        if ( $align < self::ALIGN_CENTER ) {
            $result = 0;
        } elseif ( $align > self::ALIGN_CENTER ) {
            $result = $src - $dst;
        } else {
            $result = ($src - $dst) >> 1;
        }
        return $result;
    }
}