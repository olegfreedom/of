<?php
namespace Admin\Controller;

class PagesController extends \Admin\Base\Controller
{
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $res = array(
            'pagesList' => $this->load('Pages', 'admin')->getList(),
        );

        return $this->view($res);
    }

    public function editAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        if($this->p_int('edit-form') === 1){
            $params = array(
                'content' => $this->p_string('content', '', false)
            );

            $this->load('Pages', 'admin')->edit($this->p_int('id'), $params);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }else{
            $ret = array(
                'getEdit' => $this->load('Pages', 'admin')->getOne($this->p_int('id'))
            );

            return $this->view($ret);
        }
    }

    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'content' => $this->p_string('content', '', false)
        );

        $error = array();

        $validItem = $this->load('Validator')->validStringLength($params['content'], 0, 20000);
        if($validItem == false){
            $error['content'] = $validItem;
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );

        return $this->json($ret);
    }
}