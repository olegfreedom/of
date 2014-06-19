<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 3/31/14
 * Time: 8:59 PM
 */

namespace Profile\Controller;

class UsersController extends \Profile\Base\Controller
{
    const USER_COMMENT_EDIT_MESSAGE_SUCCESS = 'Комментарий сохранен.';
    const USER_COMMENT_EDIT_MESSAGE_ERROR = 'Комментарий НЕ сохранен. Повторите попытку позже.';
    const USER_COMMENT_ADD_MESSAGE_SUCCESS = 'Комментарий добавлен.';
    const USER_COMMENT_ADD_MESSAGE_ERROR = 'Комментарий НЕ добавлен. Повторите попытку позже.';

    public function __construct()
    {
        parent::__construct();

        $this->pushTitle('Список пользователей');

    }

    public function indexAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        // $userId = $this->getUserId();
        // $page = $this->p_int('page', 1);

        $Model = $this->load('Users', 'admin');

        $usersList = array(
            $Model::TYPE_PHYSICAL => $Model->getList(array('type' => $Model::TYPE_PHYSICAL)),
            $Model::TYPE_LEGAL => $Model->getList(array('type' => $Model::TYPE_LEGAL)),
        );

        $ret = array(
            'usersList' => $usersList,
            'Model' => $Model,
        );

        return $this->view($ret);
    }

    public function viewAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');

        if ( $id > 0 )
        {
            $userId = $this->getUserId();
            $user = $this->load('Users', 'admin')->getOne($id, $userId);

            $ret = array(
                'currentUrl' => $this->getCurrentUrl(),
                'user' => $user,
                'userId' => $userId,
                'comments' => $this->load('UserComment', 'admin')->getCommentsByUserId($id, $userId),
            );

            return $this->view($ret);
        }else{

            // $this->indexAction();
        }

    }


    public function editCommentAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $id = $this->p_int('id');
        $parent_id = $this->p_int('parent_id');
        $user_id = $this->p_int('user_id');

        if($this->p_int('user-comment-form') === 1){
            $params = array(
                'user_id' => $user_id,
                'user_commented_id' => $this->getUserId(),
                'parent_id' => $parent_id == 0 ? NULL : $parent_id,
                'comment_full' => $this->p_string('comment_full'),
            );

            $Comments = new \Profile\Controller\CommentsController();
            $check = $Comments->check($params);

            if($check['status'] == true){
                if ( !empty($id) )
                {
                    $res = $this->load('UserComment', 'admin')->edit($id, $params);

                    if ($res)
                    {
                        $this->flashMessenger()->addSuccessMessage(self::USER_COMMENT_EDIT_MESSAGE_SUCCESS);
                    }
                    else
                    {
                        $this->flashMessenger()->addSuccessMessage(self::USER_COMMENT_EDIT_MESSAGE_ERROR);

                        $dump = $this->getLogStr($id, '$id') . "\n" . $this->getLogStr($params, '$params');

                        $this->logEvent('User Comment edit error, dump:' . "\n" . $dump);
                    }
                }
                else
                {
                    $res = $this->load('UserComment', 'admin')->add($params);

                    if ($res)
                    {
                        $this->flashMessenger()->addSuccessMessage(self::USER_COMMENT_ADD_MESSAGE_SUCCESS);
                    }
                    else
                    {
                        $this->flashMessenger()->addSuccessMessage(self::USER_COMMENT_EDIT_MESSAGE_ERROR);

                        $dump = $this->getLogStr($params, '$params');

                        $this->logEvent('User Comment add error, dump:' . "\n" . $dump);
                    }
                }
            }
        }
        elseif ( $id > 0 )
        {
            $this->isAjax();
            $res = $this->load('UserComment', 'admin')->getOne($id);

            return $this->json($res);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'view', 'id' => $user_id))
            );
    }

    public function removeCommentAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $id = $this->p_int('id');
        $user_id = $this->p_int('user_id');

        if ( $id > 0 && $user_id > 0 )
        {
            $user_commented_id = $this->getUserId();

            $params = array(
                'uc.id' => $id,
                'uc.user_id' => $user_id,
                'uc.user_commented_id' => $user_commented_id,
            );

            $res = $this->load('UserComment', 'admin')->removeWithChilds($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'view', 'id' => $user_id))
            );
    }

    public function upCommentAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $user_viewed_id = $this->p_int('user_id');

        if ( $id > 0 && $user_viewed_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                'user_comment_id' => $id,
                'user_id' => $user_id,
                'rating' => 1
            );

            $res = $this->load('UserCommentRating', 'admin')->add($params);

            return $this->redirect()
                ->toUrl(
                    $this->easyUrl(array('action' => 'view', 'id' => $user_viewed_id))
                );
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'index'))
            );
    }

    public function downCommentAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $user_viewed_id = $this->p_int('user_id');

        if ( $id > 0 && $user_viewed_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                'user_comment_id' => $id,
                'user_id' => $user_id,
                'rating' => -1
            );

            $res = $this->load('UserCommentRating', 'admin')->add($params);

            return $this->redirect()
                ->toUrl(
                    $this->easyUrl(array('action' => 'view', 'id' => $user_viewed_id))
                );
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'index'))
            );

    }

    public function setRatingAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $user_viewed_id = $this->p_int('id');
        $rating = $this->p_int('rating');

        if ( $user_viewed_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                'user_id' => $user_viewed_id,
                'user_voted_id' => $user_id
            );

            $res = $this->load('UserRating', 'admin')->remove($params);

            $params ['rating'] = $rating;

            $res = $this->load('UserRating', 'admin')->add($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'view', 'id' => $user_viewed_id))
            );

    }

}