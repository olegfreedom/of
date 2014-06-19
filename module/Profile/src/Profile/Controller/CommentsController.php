<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 4/9/14
 * Time: 3:52 PM
 */

namespace Profile\Controller;

use Profile\Model\CaptchaForm;

class CommentsController extends \Profile\Base\Controller
{
    private $formName = NULL;
    private $Model = NULL;
    private $RatingModel = NULL;
    private $ratingParentIdField = NULL;
    private $commentObjectIdField = NULL;
    private $tableAlias = NULL;

/********************** Сейчас задействованы только эти 2 метода  ******************************************/
    public function validatorAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'comment_full' => $this->p_string('comment_full'),
            'captcha' => $this->p_array('captcha')
        );

        $ret = $this->check($params);

        return $this->json($ret);
    }

    /**
     * @param array $params
     * @return array
     */
    public function check($params = array()){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $errorAr = array();
        $messagesAr = array();
        $Validator = $this->load('Validator');

        $form = new CaptchaForm();
        if ($params) {
            //set captcha post
            $form->setData($params);

            if ($form->isValid()) {
                $errorCap = array(
                    'captcha' => true
                );
            }else{
                $errorCap = array(
                    'captcha' => false
                );
            }
        };

        $fieldsCheckAr = array('comment_full');
        foreach ( $fieldsCheckAr as $fieldName )
        {
            $Validator->mainCheck($fieldName, $params, $errorAr, $messagesAr);
        }

        $ret = array(
            'status' => empty($errorAr),
            'error' => (is_array($errorCap)) ? (array_merge($errorAr, $errorCap)) : $errorAr,
            'messages' => $messagesAr,
        );

        return $ret;
    }
    /****************************************************************/

    public function init()
    {
        $commentType = $this->p_string('ctype');

        switch ($commentType)
        {
            case 'advert':
                $this->formName = 'comment-form';
                $this->Model = 'AdvertComment';
                $this->RatingModel = 'AdvertCommentRating';
                $this->ratingParentIdField = 'advert_comment_id';
                $this->commentObjectIdField = 'advert_id';
                $this->tableAlias = 'ac';
                break;
            case 'user':
                $this->formName = 'user-comment-form';
                $this->Model = 'AdvertUserComment';
                $this->RatingModel = 'AdvertUserCommentRating';
                $this->ratingParentIdField = 'user_comment_id';
                $this->commentObjectIdField = 'user_commented_id';
                $this->tableAlias = 'uc';
                break;

            default:
                die('Invalid Comment Type.');
                break;
        }
    }

    public function __construct()
    {
        parent::__construct();

        $this->pushTitle('Управление комментариями');
    }

    public function indexAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $userId = $this->getUserId();
        $page = $this->p_int('page', 1);

        $Model = $this->load($this->Model, 'admin');

        $params = array(
            $this->tableAlias . '.user_id' => $userId,
            'page' => $page,
        );

        $commentsList = $Model->get($params);

        $ret = array(
            'userId' => $userId,
            'Model' => $Model,
            'commentsList' => $commentsList,
        );

        return $this->view($ret);
    }


    public function editCommentAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $id = $this->p_int('id');
        $parent_id = $this->p_int('parent_id');
        $object_id = $this->p_int($this->commentObjectIdField);

        if($this->p_int($this->formName) === 1)
        {
            $params = array(
                'user_id' => $this->getUserId(),
                'advert_id' => $object_id,
                'parent_id' => $parent_id == 0 ? NULL : $parent_id,
                'comment_full' => $this->p_string('comment_full'),
            );


            $check = $this->check($params);

            if($check['status'] == true){
                if ( !empty($id) )
                {
                    $res = $this->load($this->Model, 'admin')->edit($id, $params);

                    if ($res)
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_EDIT_MESSAGE_SUCCESS);
                    }
                    else
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_EDIT_MESSAGE_ERROR);

                        $dump = $this->getLogStr($id, '$id') . "\n" . $this->getLogStr($params, '$params');

                        $this->logEvent('Comment edit error, dump:' . "\n" . $dump);
                    }
                }
                else
                {
                    $res = $this->load($this->Model, 'admin')->add($params);

                    if ($res)
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_ADD_MESSAGE_SUCCESS);
                    }
                    else
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_EDIT_MESSAGE_ERROR);

                        $dump = $this->getLogStr($params, '$params');

                        $this->logEvent('Comment add error, dump:' . "\n" . $dump);
                    }
                }
            }
        }
        elseif ( $id > 0 )
        {
            $this->isAjax();
            $res = $this->load($this->Model, 'admin')->getOne($id);

            return $this->json($res);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'view', 'id' => $object_id))
            );
    }

    public function removeCommentAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $id = $this->p_int('id');
        $object_id = $this->p_int($this->commentObjectIdField);

        if ( $id > 0 && $object_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                $this->tableAlias . '.id' => $id,
                $this->tableAlias . '.' . $this->commentObjectIdField => $object_id,
                $this->tableAlias . '.user_id' => $user_id,
            );

            $res = $this->load($this->Model, 'admin')->removeWithChilds($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'view', 'id' => $object_id))
            );
    }

    public function upCommentAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->changeCommentRatingValue(1);
    }

    public function downCommentAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->changeCommentRatingValue(-1);
    }

    private function changeCommentRatingValue($byValue = 0)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $object_id = $this->p_int($this->commentObjectIdField);

        if ( $id > 0 && $object_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                $this->ratingParentIdField => $id,
                'user_id' => $user_id,
                'rating' => $byValue,
            );

            $res = $this->load($this->RatingModel, 'admin')->add($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('action' => 'view', 'id' => $object_id))
            );
    }

}