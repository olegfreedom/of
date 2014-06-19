<?php
namespace Admin\Model;

class Date extends \Application\Base\Model
{
    /**
     * Get days left
     * @param int $timestamp
     * @return int
     */
    public function daysLeft($timestamp = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = 0;
        $time = time();
        
        if($timestamp > $time){
            $ret = floor( ($timestamp - $time) / (60*60*24) );
        }
        
        return $ret;
    }
    
    /**
     * Generate interval
     * @param int $timestamp
     * @param string $interval strtotime interval
     * @return int
     */
    public function setInterval($timestamp = 0, $interval = '+1 month'){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = 0;
        $time = time();
        
        if($timestamp > $time){
            $ret = strtotime($interval, $timestamp);
        }else{
            $ret = strtotime($interval, $time);
        }
        
        return $ret;
    }

    /**
     * @param string $data
     * @return string
     */
    public function translateMonth($data)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $monthesEng = array(
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        );

        $monthesRus = array(
            'Января', 'Февраля', 'Марта', 'Апреля', 'Мая', 'Июня', 'Июля',
            'Августа', 'Сентября', 'Октября', 'Ноября', 'Декабря'
        );

        $translate = str_replace($monthesEng, $monthesRus, $data);

        return $translate;
    }

    /**
     * @param integer $daysLeft
     * @param string $type
     * @return string
     */
    public function daysText($daysLeft = 0, $type = 'profile')
    {
        $text = array(
            'profile' => array(
                1 => 'дней осталось',
                2 => 'день остался',
                3 => 'дня осталось'
            ),
            'sendmail' => array(
                1 => 'дней',
                2 => 'день',
                3 => 'дня'
            )
        );

        if ($daysLeft > 20){
            $daysLeft = (int)substr((string)$daysLeft, -1);
        }

        if ($daysLeft > 4 || $daysLeft == 0) {
            $daysText = isset($text[$type][1]) ? $text[$type][1] : 'дней';
        } else if ($daysLeft == 1) {
            $daysText = isset($text[$type][2]) ? $text[$type][2] : 'день';
        } else {
            $daysText = isset($text[$type][3]) ? $text[$type][3] : 'дня';
        }
        return $daysText;
    }
    
    /**
     * Get data and time
     * @return date
     */
    public function getDateTime(){
        return date('Y-m-d H:i:s');
    }
}
