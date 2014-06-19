<?php
namespace Admin\Model;

use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mail\Transport\Sendmail as SendmailTransport;

class SendEmail extends \Application\Base\Model
{
    /**
     * Send Email
     * @param string $email
     * @param string $title
     * @param string $content
     */
    private function sendEmail($email = null, $title = null, $content = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;
        
        if($email !== null && $title !== null && $content !== null){
            
            $htmlTpl = '<html>';
            $htmlTpl .= '<head>';
            $htmlTpl .= '<meta charset="utf-8">';
            $htmlTpl .= '<title>'.$title.'</title>';
            $htmlTpl .= '</head>';
            $htmlTpl .= '<body>';
            $htmlTpl .= $content;
            $htmlTpl .= '<br><br><hr><a href="' . $this->basePath() . '">'. $this->basePath() . '</a>';
            $htmlTpl .= '</body>';
            $htmlTpl .= '</html>';

            $html = new MimePart($htmlTpl);
            $html->type = 'text/html';
            $html->charset = 'utf-8';

            $body = new MimeMessage();
            $body->setParts(array($html));

            $message = new Message();
            $message->setTo($email)
                    ->setFrom($this->getSiteEmail(), $this->getSiteName())
                    ->setReplyTo($this->getSiteEmail(), $this->getSiteName())
                    ->setSubject($title)
                    ->setBody($body)
                    ->setEncoding('UTF-8');

            $transport = new SendmailTransport();
            $ret = $transport->send($message);
        }

        return $ret;
    }

    /**
     * Get e-mail template
     * @param string $url
     * @return array
     */
    public function getNotification($url = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $ret = null;
        
        if($url !== null){
            $select = $this->select()
                ->from(self::TABLE_EMAIL_NOTIFICATIONS)
                ->columns(array(
                    'title',
                    'text'
                ))
                ->where(array(
                    'url' => $url,
                ));

            $result = $this->fetchRowSelect($select);
            
            if($result){
                $ret = $result;
            }
        }
        return $ret;
    }

    /**
     * Create e-mail for registration
     * @param string $username
     */
    public function registration($username = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ($username !== null) {            
            $result = $this->getNotification(self::EMAIL_REGISTRATION);

            if (isset($result['text']) && isset($result['title'])) {
                $message = $result['text'];
                $message = str_replace('##login##', $username, $message);
                $this->sendEmail($username, $result['title'], $message);
            }            
        }
    }

    /**
     * Create e-mail for activation user
     * @param string $username
     * @param string $key
     * @param string $activationUrl
     */
    public function activationUser($username = null, $key = null, $activationUrl = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($username !== null && $key !== null && $activationUrl !== null) {
            $result = $this->getNotification(self::EMAIL_ACTIVATION_USER);

            if (isset($result['text']) && isset($result['title'])) {
                $message = $result['text'];
                $link = str_replace('_SET_KEY_', $key, $activationUrl);
                $message = str_replace('##link##', $link, $message);

                $this->sendEmail($username, $result['title'], $message);
            }
        }
    }

    /**
     * Create e-mail for change email
     * @param string $username
     * @param string $changeUrl
     */
    public function changeEmail($username = null, $changeUrl = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($username !== null && $changeUrl !== null) {
            $result = $this->getNotification(self::EMAIL_CHANGE_EMAIL);

            if ($result) {
                $message = $result['text'];
                $message = str_replace('##link##', $changeUrl, $message);

                $this->sendEmail($username, $result['title'], $message);
            }
        }
    }

    /**
     * Create e-mail for change user data
     * @param string $username
     * @param string $password
     */
    public function changeData($username = null, $password = null, $url = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ($username !== null && $password !== null) {
            $result = $this->getNotification(self::EMAIL_CHANGE_DATA);

            if ($result) {
                $message = $result['text'];
                $message = str_replace('##loginUrl##', $url, $message);
                $message = str_replace('##login##', $username, $message);
                $message = str_replace('##password##', $password, $message);

                $this->sendEmail($username, $result['title'], $message);
            }            
        }
    }

    /**
     * Create e-mail for change user email
     * @param string $username
     */
    public function changeEmailSuccess($username = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($username !== null) {
            $result = $this->getNotification(self::EMAIL_CHANGE_EMAIL_SUCCESS);

            if ($result) {
                $message = $result['text'];
                $message = str_replace('##login##', $username, $message);

                $this->sendEmail($username, $result['title'], $message);
            }
        }
    }

    /**
     * Create e-mail for add car
     * @param string $title
     * @param int $user_id
     */
    public function addAdvert($title = null, $user_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ($title !== null && (int)$user_id > 0) {            
            if($this->load('Users', 'admin')->getLevel($user_id) == self::USERS_LEVEL_USER){
                $result = $this->getNotification(self::EMAIL_ADD_ADVERT);

                if ($result) {
                    $message = $result['text'];
                    $message = str_replace('##title##', $title, $message);
                    $username = $this->load('Users', 'admin')->getUsername($user_id);
                    $this->sendEmail($username, $result['title'], $message);
                }
            }
        }

    }

    /**
     * Create e-mail for remove advert
     * @param string $title
     * @param int $user_id
     */
    public function deleteAdvert($title = null, $user_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ($title !== null && (int)$user_id > 0) {            
            if ($this->load('Users', 'admin')->getLevel($user_id) == self::USERS_LEVEL_USER) {
                $result = $this->getNotification(self::EMAIL_DELETE_ADVERT);

                if ($result) {
                    $message = $result['text'];
                    $message = str_replace('##title##', $title, $message);
                    $username = $this->load('Users', 'admin')->getUsername($user_id);
                    $this->sendEmail($username, $result['title'], $message);
                }
            }
        }

    }

    /**
     * Create e-mail for change status active/inactive
     * @param integer $id
     * @param array $params
     * @param string $title
     * @param int $user_id
     * @param string $status
     */
    public function activationAdvert($id = 0, $title = null, $user_id = 0, $status = null, $params = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($title !== null && (int)$user_id > 0 && $status !== null) {
            if ($this->load('Users', 'admin')->getLevel($user_id) == self::USERS_LEVEL_USER) {
                $result = $this->getNotification(self::EMAIL_ACTIVATION_ADVERT);
                
                if ($result) {
                    $statusText = ($status == 'n' ? 'активировано' : 'заблокировано');
                    $message = $result['text'];
                    $message = str_replace('##advUrl##', $params['advUrl'], $message);
                    $message = str_replace('##uslugi##', $params['servicesUrl'], $message);
                    $message = str_replace('##title##', $title, $message);
                    $message = str_replace('##message##', $statusText, $message);
                    $username = $this->load('Users', 'admin')->getUsername($user_id);
                    $this->sendEmail($username, $result['title'], $message);
                }
            }
        }

    }

    /**
     * Create e-mail for prolong time
     * @param string $title
     * @param int $user_id
     */
    public function prolongTime($title = null, $user_id = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ($title !== null && (int)$user_id > 0) {            
            if ($this->load('Users', 'admin')->getLevel($user_id) == self::USERS_LEVEL_USER) {
                $result = $this->getNotification(self::EMAIL_EXTEND_TIME);

                if ($result) {
                    $message = $result['text'];
                    $message = str_replace('##title##', $title, $message);
                    $username = $this->load('Users', 'admin')->getUsername($user_id);
                    $this->sendEmail($username, $result['title'], $message);
                }
            }
        }

    }


    /**
     * Create daily e-mail for expiry time in all categories
     * @param integer $days
     */
    public function expiryTime($days = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if($days > 0){     
            $ret = array();

            $tables = array(
                self::TABLE_VEHICLES_CAR_RENT,
                self::TABLE_VEHICLES_FREIGHT_TRANSPORTATION,
                self::TABLE_VEHICLES_PASSENGER_TRANSPORTATION,
                self::TABLE_VEHICLES_SPECIAL_MACHINERY
            );

            foreach ($tables as $table){

                $select = $this->select()
                    ->from(array('v' => $table))
                    ->columns(array(
                        'name',
                        'user_id',
                    ))
                    ->join(
                        array('u' => self::TABLE_USER),
                        'u.id = user_id',
                        array('username' => 'username',
                        )
                    )
                    ->join(
                        array('m2c' => self::TABLE_VEHICLES_MODEL_TO_CAT),
                        'v.model = m2c.id',
                        array()
                    )
                    ->join(
                        array('m' => self::TABLE_VEHICLES_MODEL),
                        'm2c.model_id = m.id',
                        array('model' => 'name')
                    )
                    ->where(array(
                        'u.level' => self::USERS_LEVEL_USER,
                        $this->where()
                            ->literal('unix_timestamp(v.lifetime) > (unix_timestamp(now()) + 60*60*24*' . ($days - 1) . ')')
                            ->and
                            ->literal('unix_timestamp(v.lifetime) < (unix_timestamp(now()) + 60*60*24*' . ($days + 1) . ')')
                    ));

                $result = $this->fetchSelect($select);

                if ($result) {
                    foreach ($result as $item) {
                        if (isset($item['user_id'])) {
                            if (!isset($ret[$item['user_id']])) {
                                $ret[$item['user_id']] = array();
                            }

                            $ret[$item['user_id']][] = $item;
                        }
                    }
                }

            }

            if (count($ret) > 0) {

                $notification = $this->getNotification(self::EMAIL_EXPIRY_TIME);
                $message_tpl = $notification['text'];
                foreach ($ret as $item) {
                    $carList = '<ul>';
                    foreach ($item as $val) {
                        $carList .= '<li>'.$val['model'].' '.$val['name'].'</li>'."\n";
                        $username = $val['username'];
                    }
                    $carList .= '</ul>';
                    
                    if(isset($username)){                        
                        $message = str_replace('##title##', $carList, $message_tpl);
                        $message = str_replace('##num##', $days, $message);
                        $message = str_replace('##days_text##', $this->load('Date', 'admin')->daysText($days, 'sendmail'), $message);
                        $this->sendEmail($username, $notification['title'], $message);
                    }
                }

            }
        }
    }

    /**
     * Create e-mail for pay stars
     * @param string $username
     * @param int $stars
     */
    public function refill($username = null, $stars = 0){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ($username !== null && (int)$stars > 0) {            
            $result = $this->getNotification(self::EMAIL_REFILL);

            if ($result) {
                $message = $result['text'];
                $message = str_replace('##num##', $stars, $message);
                $message = str_replace('##stars##', $this->load('Wallet', 'profile')->starsText($stars, 'stars'), $message);
                $this->sendEmail($username, $result['title'], $message);
            }
        }
    }

    /**
     * Create e-mail for forgot password
     * @param string $email
     * @param string $newPassword
     */
    public function forgotPassword($email = null, $newPassword = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if ($email !== null && $newPassword !== null) {
            $result = $this->getNotification(self::EMAIL_FORGOT);

            if ($result) {
                $message = $result['text'];
                $message = str_replace('##new_password##', $newPassword, $message);
                $this->sendEmail($email, $result['title'], $message);
            }
        }
    }
    
    /**
     * Create e-mail for password recovery confirmation
     * @param array $email
     * @param string $key
     * @param string $activationUrl
     */
    public function recoveryConfirmation($params, $key = null){ // , $activationUrl = null
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = false;

        if ($params['username'] !== null && $key !== null) {
            $result = $this->getNotification(self::EMAIL_CONFIRM_RECOVERY);

            if ($result) {
                $message = $result['text'];
                $link = str_replace('__KEY__', $key, $params['link']);
                $message = str_replace('##login##', $params['username'], $message);
                $message = str_replace('##recovery_url##', $link, $message);
                $message = str_replace('##site_name##', $_SERVER ['SERVER_NAME'], $message);
                $this->sendEmail($params['username'], $result['title'], $message);
            }
        }
    }

    /**
     * Create e-mail for guest account
     * @param string $email
     * @param string $password
     * @param string $key
     * @param string $activationUrl
     */
    public function guestActivate($email = null, $password = null, $key = null, $activationUrl = null){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($email !== null && $password !== null) {
            $result = $this->getNotification(self::EMAIL_FOR_GUEST);

            if ($result) {
                $message = $result['text'];
                $link = str_replace('_SET_KEY_', $key, $activationUrl);
                $message = str_replace('##login##', $email, $message);
                $message = str_replace('##password##', $password, $message);
                $message = str_replace('##link##', $link, $message);
                $this->sendEmail($email, $result['title'], $message);
            }
        }
    }
    
}