<?php
namespace Base\Text;

use Zend\Filter\StringToLower;
use Zend\Filter\PregReplace;

class Translit 
{
    /**
     * 
     * @param string $str
     * @param string $separator
     * @param boolean $lowercase
     * @return string
     */
    public function __invoke($str = '', $separator = '-', $lowercase = true) {
        $str = $this->textConvert($str);

        if($lowercase === true){            
            $stringToLower = new StringToLower();
            $stringToLower->setEncoding('UTF-8');
            $str = $stringToLower->filter($str);
        }
        
        // remove unnecessary characters and add separator instead of a space
        $pregReplace = new PregReplace();
        
        $pregReplace->setPattern('/[^a-z0-9\s]+/');
        $pregReplace->setReplacement(' ');
        $str = $pregReplace->filter($str);
        
        $pregReplace->setPattern('/\s+/');
        $pregReplace->setReplacement($separator);
        $str = $pregReplace->filter($str);
        
        return $str;
    }
    
    /**
     * 
     * @param string $str
     * @return string
     */
    private function textConvert($str = ''){
        if(!empty($str)){
            // First change "single character" phoneme words
            $str = strtr($str, array(
                "а" => "a", "б" => "b", "в" => "v", "г" => "g", "д" => "d", 
                "е" => "e", "ё" => "e", "з" => "z", "и" => "i", "й" => "y", 
                "к" => "k", "л" => "l", "м" => "m", "н" => "n", "о" => "o", 
                "п" => "p", "р" => "r", "с" => "s", "т" => "t", "у" => "u", 
                "ф" => "f", "х" => "h", "ъ" => "", "ы" => "i", "э" => "e" 
            )); // lowercase
            $str = strtr($str, array(
                "А" => "A", "Б" => "B", "В" => "V", "Г" => "G", "Д" => "D", 
                "Е" => "E", "Ё" => "E", "З" => "Z", "И" => "I", "Й" => "Y", 
                "К" => "K", "Л" => "L", "М" => "M", "Н" => "N", "О" => "O", 
                "П" => "P", "Р" => "R", "С" => "S", "Т" => "T", "У" => "U", 
                "Ф" => "F", "Х" => "H", "Ъ" => "", "Ы" => "I", "Э" => "E" 
            )); // UPPERCASE

            // And then "multi-character"
            $str = strtr($str, array(
                "ж"=>"zh", "ц"=>"ts", "ч"=>"ch", "ш"=>"sh", 
                "щ"=>"shch","ь"=>"", "ю"=>"yu", "я"=>"ya",
                "Ж"=>"ZH", "Ц"=>"TS", "Ч"=>"CH", "Ш"=>"SH", 
                "Щ"=>"SHCH","Ь"=>"", "Ю"=>"YU", "Я"=>"YA",
                "ї"=>"i", "Ї"=>"Yi", "є"=>"ie", "Є"=>"Ye"
            ));            
        }
        
        return $str;
    }
}