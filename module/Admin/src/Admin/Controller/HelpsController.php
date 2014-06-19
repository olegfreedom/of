<?php
namespace Admin\Controller;

class HelpsController extends \Admin\Base\Controller
{
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $res = array(
            'helpsList' => $this->load('Helps', 'admin')->getList(),
        );

        return $this->view($res);
    }

    public function editAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        if($this->p_int('edit-form') === 1){
            $params = array(
                'text' => $this->p_string('text', '', false)
            );

            $this->load('Helps', 'admin')->edit($this->p_int('id'), $params);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }else{
            $ret = array(
                'getEdit' => $this->load('Helps', 'admin')->getOne($this->p_int('id'))
            );

            return $this->view($ret);
        }
    }

    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'text' => $this->p_string('text', '', false)
        );

        $error = array();

        $validItem = $this->load('Validator')->validStringLength($params['text'], 10, 10000);
        if($validItem == false){
            $error['text'] = $validItem;
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );

        return $this->json($ret);
    }
}