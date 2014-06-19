<?php
namespace Profile\Controller;

class MessagesController extends \Profile\Base\Controller
{
    public function indexAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = array(
            'inboxList' => $this->load('Messages', 'profile')->getMessagesByAdverts($this->getUserId())
        );

        return $this->view($ret);
    }

    public function talkAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $advert_id = $this->p_int('advert');
        $from_user_id = $this->p_int('user');

        $this->load('Messages', 'profile')->readMessage($advert_id, $this->getUserId());

        $ret = array(
            'messagesList' => $this->load('Messages', 'profile')->getTalkList($advert_id, $this->getUserId(), $from_user_id),
            'user' => $this->load('User', 'profile')->getOne($from_user_id),
            'advertId' => $advert_id
        );

        return $this->view($ret);
    }

    public function archiveAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = array(
            'archiveList' => $this->load('Messages', 'profile')->getArchiveList($this->getUserId())
        );

        return $this->view($ret);
    }

    public function addAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($this->p_int('add-form') === 1) {
            $advertId = $this->p_int('advert_id');
            $userId = $this->p_int('to_user_id');
            $params = array(
                'title' => $this->p_string('title'),
                'text' => $this->p_string('text'),
                'from_user_id' => $this->getUserId(),
                'to_user_id' => $userId
            );
            
            $this->load('Messages', 'profile')->addMessage($params, $advertId);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action' => 'talk', 'user' => $userId, 'advert' => $advertId)));
        }

        $ret = array();

        return $this->view($ret);
    }
    
    // ADD POPUP MESSAGE
    
    public function addSmsAction() {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $this->isAjax();
        
        $advertId = $this->p_int('advert_id');
        $params = array (
            'title' => 'Сообщение автору',
            'text' => $this->p_string('text'),
            'from_user_id' => $this->getUserId(),
            'to_user_id' => $this->p_int('to_user_id')
        );

        $ret = array(
            'status' => $this->load('Messages', 'profile')->addMessage($params, $advertId)
        );
        
        return $this->json($ret);
    }
    
    public function validatorSmsAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'text' => $this->p_string('text')
        );
        
        $error = array();
        
        $validItem = $this->load('Validator')->validStringLength($params['text'], 2, 20000);
        if($validItem == false){
            $error['text'] = $validItem;
        }
        
        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $this->json($ret);
    }
    
    // END POPUP MESSAGE

    public function replyAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $this->load('Messages', 'profile')->readMessage($id);

        if ($this->p_int('add-form') === 1) {
            $title = $this->p_string('title');
            $count = preg_match('/^Re(\((?P<count>([0-9]+))\))?/', $title, $matches);
            if ($count == 0) {
                $title = 'Re: '.$title;
            } else {
                $title = preg_replace('/^Re(\([0-9]+\))?/', isset($matches['count']) ? 'Re('.++$matches['count'].')' : 'Re(2)', $title);
            }
            $params = array(
                'title' => $title,
                'text' => $this->p_string('text'),
                'from_user_id' => $this->getUserId(),
                'to_user_id' => $this->load('User', 'profile')->getIdByUsername($this->p_string('username'))
            );

            $this->load('Messages', 'profile')->addMessage($params);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action' => 'index')));
        }

        $ret = array(
            'getOne' => $this->load('Messages', 'profile')->getOne($id)
        );

        return $this->view($ret);
    }

    public function deleteAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $messageId = $this->p_int('messageId');
        $type = $this->p_string('type');
        $action = $this->p_string('action');

        $ret = array(
            'deleteResult' => $this->load('Messages', 'profile')->deleteMessage($messageId, $action, $type)
        );
        return $this->json($ret);
    }

    public function getUsersAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $username = $this->p_string('username');

        $ret = array(
            'usersList' => $this->load('User', 'profile')->getUsersList($username, $this->getUserId())
        );
        return $this->json($ret);
    }

    public function validatorAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'title' => $this->p_string('title'),
            'username' => $this->p_string('username'),
            'text' => $this->p_string('text')
        );

        $error = array();

//        $validItem = $this->load('Validator')->validStringLength($params['username'], 5, 100); TODO: на время закоментировано так как не используется
//        if($validItem == false){
//            $error['username'] = $validItem;
//        }else{
//            $validItem = $this->load('Validator')->validEmail($params['username']);
//            if($validItem == false){
//                $error['username'] = $validItem;
//            } else {
//                $validItem = $this->load('User', 'profile')->checkLogin($params['username']);
//                if($validItem == false){
//                    $error['username'] = $validItem;
//                }
//            }
//        }

//        $validItem = $this->load('Validator')->validStringLength($params['title'], 2, 50);
//        if ($validItem == false) {
//            $error['title'] = $validItem;
//        }


        $validItem = $this->load('Validator')->validStringLength($params['text'], 2, 20000);
        if ($validItem == false) {
            $error['text'] = $validItem;
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );

        return $this->json($ret);
    }
}