<?php
namespace Profile\Model;

class Wallet extends \Application\Base\Model
{
    const PRICE_RATE = 100;
    
    /**
     * Get User Info
     * @param int $userId
     * @return array
     */
    public function get($userId = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
            if((int)$userId > 0){
                $select = $this->select()
                               ->from(self::TABLE_USER)
                               ->columns(array(
                                   'star'
                               ))
                               ->where(array(
                                   'id' => $userId
                               ))
                               ->limit(1);
                
                $result = $this->fetchOneSelect($select);
                
                if($result){
                    $ret = $result;
                }
            }
        
        return $ret;
    }

    /**
     * @param integer $stars
     * @param string $type
     * @return string
     */
    public function starsText($stars = 0, $type = 'stars')
    {
        $text = array(
            'stars' => array(
                1 => 'звезд',
                2 => 'звезду',
                3 => 'звезды'
            )
        );

        if ($stars > 20){
            $stars = (int)substr((string)$stars, -1);
        }

        if ($stars > 4 || $stars == 0) {
            $starsText = isset($text[$type][1]) ? $text[$type][1] : 'дней';
        } else if ($stars == 1) {
            $starsText = isset($text[$type][2]) ? $text[$type][2] : 'день';
        } else {
            $starsText = isset($text[$type][3]) ? $text[$type][3] : 'дня';
        }
        return $starsText;
    }
}