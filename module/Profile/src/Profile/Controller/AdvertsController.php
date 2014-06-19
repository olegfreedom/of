<?php
namespace Profile\Controller;

class AdvertsController extends \Profile\Base\Controller //\Profile\Base\AbstractAdvertsController
{
    const ADVERT_ADD_MESSAGE_SUCCESS = 'Объявление добавлено.';
    const ADVERT_ADD_MESSAGE_ERROR = 'Объявление НЕ добавлено. Повторите попытку позже.';
    const ADVERT_EDIT_MESSAGE_SUCCESS = 'Объявление сохранено.';
    const ADVERT_EDIT_MESSAGE_ERROR = 'Объявление НЕ сохранено. Повторите попытку позже.';

    const COMMENT_EDIT_MESSAGE_SUCCESS = 'Комментарий сохранен.';
    const COMMENT_EDIT_MESSAGE_ERROR = 'Комментарий НЕ сохранен. Повторите попытку позже.';
    const COMMENT_ADD_MESSAGE_SUCCESS = 'Комментарий добавлен.';
    const COMMENT_ADD_MESSAGE_ERROR = 'Комментарий НЕ добавлен. Повторите попытку позже.';

    public function __construct()
    {
        parent::__construct();

        $this->pushTitle('Управление объявлениями');

    }

    public function indexAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $userId = $this->getUserId();
        $page = $this->p_int('page', 1);

        $Model = $this->load('Adverts', 'admin');

        $params = array(
            'user_id' => $userId,
            'page' => $page,
            'type' => $Model::TYPE_ALL,
        );

        $advertsList = $Model->getList($params);

        $ret = array(
            'Model' => $Model,
            'advertsList' => $advertsList,
        );

        return $this->view($ret);
    }

    public function addAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($this->p_int('add-form') === 1) {
            $params = array(
                'type' => $this->p_int('type_id'),
                'location' => 8, /* TODO: REMOVE HARDCODED VALUE */ // $this->p_int('location'),
                'name' => $this->p_string('name'),
                // 'contact_name' => $this->p_string('contact_name'),
                'description_short' => $this->p_string('description_short', '', false),
                'description_full' => $this->p_string('description_full', '', false),
                'price' => $this->p_float('price'),
                'currency' => 1, /* TODO: REMOVE HARDCODED VALUE */ // $this->p_int('currency'),
                'unit_type' => $this->p_int('unit_type'),
                'category' => $this->p_int('category_id'),
                'status' => 'n',
                'user_id' => $this->getUserId()
            );

            $arrays = array(
                'gallery' => $this->getFiles('gallery'),
                'date_from' => $this->p_array('date_from'),
                'date_to' => $this->p_array('date_to'),
            );

            if ( $this->load('Adverts', 'admin')->add($params, $arrays) )
            {
                $this->flashMessenger()->addSuccessMessage(self::ADVERT_ADD_MESSAGE_SUCCESS);
            }
            else
            {
                $this->flashMessenger()->addErrorMessage(self::ADVERT_ADD_MESSAGE_ERROR);

                $dump = $this->getLogStr($params, '$params') . "\n" . $this->getLogStr($arrays, '$arrays');

                $this->logEvent('Advert add error, dump:' . "\n" . $dump);
            }

            return $this->redirect()->toUrl(
                $this->easyUrl(array('controller' => 'adverts'))
            );
        } else {
            $ret = array(
                'getType' => $this->load('AdvertType', 'admin')->get(),
                'type' => $this->p_int('type'),
                'getLocation' => $this->load('AdvertLocation', 'admin')->getRegions(),
                'getCurrency' => $this->load('AdvertCurrency', 'admin')->get(),
                'getUnitTypes' => $this->load('AdvertUnitType', 'admin')->get(),
                'getCategory' => $this->load('AdvertCategory', 'admin')->get(),
                'helper' => $this->load('Helps', 'admin')->getTextByUrl(array(
                        'type',
                        'name',
                        'contact_name',
                        'category',
                        'phone',
                        'location',
                        'description',
                        'price',
                    ))
            );

            return $this->view($ret);
        }
    }

    public function editAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($this->p_int('edit-form') === 1) {

            $params = array(
                'type' => $this->p_int('type_id'),
                'location' => 8, /* TODO: REMOVE HARDCODED VALUE */ // $this->p_int('location'),
                'name' => $this->p_string('name'),
                'description_short' => $this->p_string('description_short', '', false),
                'description_full' => $this->p_string('description_full', '', false),
                'price' => $this->p_float('price'),
                'currency' => 1, /* TODO: REMOVE HARDCODED VALUE */ // $this->p_int('currency'),
                'unit_type' => $this->p_int('unit_type'),
                'category' => $this->p_int('category_id'),
                'status' => 'n',
                'user_id' => $this->getUserId()
            );

            $arrays = array(
                'date_from' => $this->p_array('date_from'),
                'date_to' => $this->p_array('date_to'),
                'gallery' => $this->getFiles('gallery'),
            );

            if ( $this->load('Adverts', 'admin')->edit($this->p_int('id'), $params, $arrays, $this->getUserId()) )
            {
                $this->flashMessenger()->addSuccessMessage(self::ADVERT_EDIT_MESSAGE_SUCCESS);
            }
            else
            {
                $this->flashMessenger()->addErrorMessage(self::ADVERT_EDIT_MESSAGE_ERROR);

                $dump = $this->getLogStr($this->p_int('id'), '$id') . "\n" . $this->getLogStr($this->getUserId(), '$this->getUserId()') . "\n" .
                        $this->getLogStr($params, '$params') . "\n" . $this->getLogStr($arrays, '$arrays');

                $this->logEvent('Advert edit error, dump:' . "\n" . $dump);
            }

            return $this->redirect()->toUrl(
                $this->easyUrl(array('controller' => 'adverts'))
            );
        } else {
            $id = $this->p_int('id');
            $getOne = $this->load('Adverts', 'admin')->getOne($id);
            // $region = $this->load('AdvertLocation', 'admin')->getRegionByCity($getOne['location']);
            $ret = array(
                'getEdit' => $getOne,
                'getType' => $this->load('AdvertType', 'admin')->get(),
                'getCurrency' => $this->load('AdvertCurrency', 'admin')->get(),
                'getUnitTypes' => $this->load('AdvertUnitType', 'admin')->get(),
                'getCategory' => $this->load('AdvertCategory', 'admin')->get(),
                'getGallery' => $this->load('AdvertGallery', 'admin')->generateURL($id, $this),
                'getDate' => $this->load('AdvertDate', 'admin')->get(array('advert_id' => $id)),
                'helper' => $this->load('Helps', 'admin')->getTextByUrl(array(
                        'type',
                        'name',
                        'contact_name',
                        'category',
                        'phone',
                        'location',
                        'description',
                        'price',
                    ))
            );

            return $this->view($ret);
        }
    }

    public function removeAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');

        $this->load('Adverts', 'admin')->remove($id, $this->getUserId());

        return $this->redirect()->toUrl(
            $this->easyUrl(array('controller' => 'adverts'))
        );
    }

    public function validatorAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'name' => $this->p_string('name'),
            'description_full' => $this->p_string('description_full', '', false),
            'price' => $this->p_float('price'),
            'category_id' => $this->p_int('category_id'),
            'type_id' => $this->p_int('type_id'),
        );

        $errorAr = array();
        $messagesAr = array();
        $Validator = $this->load('Validator');

        $fieldsCheckAr = array('name', 'category_id', 'type_id', 'description_full', 'price');
        foreach ( $fieldsCheckAr as $fieldName )
        {
            $Validator->mainCheck($fieldName, $params, $errorAr, $messagesAr);
        }

        $ret = array(
            'status' => empty($errorAr),
            'error' => $errorAr,
            'messages' => $messagesAr,
        );

        return $this->json($ret);
    }

    public function removePhoneAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $id = $this->p_int('id');
        $advert = $this->p_int('advert');

        $ret = array(
            'status' => $this->load('AdvertPhone', 'admin')->remove($advert, $id, $this->getUserId())
        );

        return $this->json($ret);
    }

    public function removeGalleryAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $id = $this->p_int('id');
        $advert = $this->p_int('advert');

        $ret = array(
            'status' => $this->load('AdvertGallery', 'admin')->remove($advert, $id)
        );

        return $this->json($ret);
    }

    public function editCommentAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        $id = $this->p_int('id');
        $parent_id = $this->p_int('parent_id');
        $advert_id = $this->p_int('advert_id');

        if($this->p_int('comment-form') === 1){
            $params = array(
                'user_id' => $this->getUserId(),
                'advert_id' => $advert_id,
                'parent_id' => $parent_id == 0 ? NULL : $parent_id,
                'comment_full' => $this->p_string('comment_full'),
            );

            $Comments = new \Profile\Controller\CommentsController();
            $check = $Comments->check($params);

            if($check['status'] == true){
                if ( !empty($id) )
                {
                    $res = $this->load('AdvertComment', 'admin')->edit($id, $params);

                    if ($res)
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_EDIT_MESSAGE_SUCCESS);
                    }
                    else
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_EDIT_MESSAGE_ERROR);

                        $dump = $this->getLogStr($id, '$id') . "\n" . $this->getLogStr($params, '$params');

                        $this->logEvent('Advert Comment edit error, dump:' . "\n" . $dump);
                    }
                }
                else
                {
                    $res = $this->load('AdvertComment', 'admin')->add($params);

                    if ($res)
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_ADD_MESSAGE_SUCCESS);
                    }
                    else
                    {
                        $this->flashMessenger()->addSuccessMessage(self::COMMENT_EDIT_MESSAGE_ERROR);

                        $dump = $this->getLogStr($params, '$params');

                        $this->logEvent('Advert Comment add error, dump:' . "\n" . $dump);
                    }
                }
            }
        }
        elseif ( $id > 0 )
        {
            $this->isAjax();
            $res = $this->load('AdvertComment', 'admin')->getOne($id);

            return $this->json($res);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('module' => 'application', 'controller' => 'adverts', 'action' => 'view', 'id' => $advert_id))
            );
    }

    public function removeCommentAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $id = $this->p_int('id');
        $advert_id = $this->p_int('advert_id');

        if ( $id > 0 && $advert_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                'ac.id' => $id,
                'ac.advert_id' => $advert_id,
                'ac.user_id' => $user_id,
            );

            $res = $this->load('AdvertComment', 'admin')->removeWithChilds($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('module' => 'application', 'controller' => 'adverts', 'action' => 'view', 'id' => $advert_id))
            );
    }

    public function upCommentAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $advert_id = $this->p_int('advert_id');

        if ( $id > 0 && $advert_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                'advert_comment_id' => $id,
                'user_id' => $user_id,
                'rating' => 1
            );

            $res = $this->load('AdvertCommentRating', 'admin')->add($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('module' => 'application', 'controller' => 'adverts', 'action' => 'view', 'id' => $advert_id))
            );
    }

    public function downCommentAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $advert_id = $this->p_int('advert_id');

        if ( $id > 0 && $advert_id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                'advert_comment_id' => $id,
                'user_id' => $user_id,
                'rating' => -1
            );

            $res = $this->load('AdvertCommentRating', 'admin')->add($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('module' => 'application', 'controller' => 'adverts', 'action' => 'view', 'id' => $advert_id))
            );
    }

    public function setRatingAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $rating = $this->p_int('rating');

        if ( $id > 0 )
        {
            $user_id = $this->getUserId();

            $params = array(
                'advert_id' => $id,
                'user_id' => $user_id
            );

            $res = $this->load('AdvertRating', 'admin')->remove($params);

            $params ['rating'] = $rating;

            $res = $this->load('AdvertRating', 'admin')->add($params);
        }

        return $this->redirect()
            ->toUrl(
                $this->easyUrl(array('module' => 'application', 'controller' => 'adverts', 'action' => 'view', 'id' => $id))
            );

    }


}
