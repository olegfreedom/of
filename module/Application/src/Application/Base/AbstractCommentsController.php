<?php
/**
 * Created by PhpStorm.
 * User: work
 * Date: 5/16/14
 * Time: 11:18 AM
 */

namespace Application\Base;

abstract class AbstractCommentsController extends Controller {

    protected $formName = 'comment-form';
    protected $Model = 'AdvertComment';
    protected $RatingModel = 'AdvertCommentRating';
    protected $ratingParentIdField = 'advert_comment_id';
    protected $commentObjectIdField = 'advert_id';
    protected $tableAlias = 'ac';

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

        $this->changeCommentValue(1);
    }

    public function downCommentAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->changeCommentValue(-1);
    }

    private function changeCommentValue($byValue = 0)
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