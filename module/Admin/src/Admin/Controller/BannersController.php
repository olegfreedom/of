<?php
namespace Admin\Controller;

class BannersController extends \Admin\Base\Controller
{
    public function indexAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $ret = array(
            'bannersList' => $this->load('Banners', 'admin')->getList()
        );

        return $this->view($ret);
    }

    public function addAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        if($this->p_int('add-form') === 1){
            $params = array(
                'title' => $this->p_string('title'),
                'url' => $this->p_string('url')
            );

            $height = $this->p_int('height');
            $img = $this->getFiles('img');

            $this->load('Banners', 'admin')->add($params, $img, $height);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }else{
            $ret = array();

            return $this->view($ret);
        }
    }

    public function editAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $id = $this->p_int('id');

        if($this->p_int('edit-form') === 1){
            $params = array(
                'title' => $this->p_string('title'),
                'url' => $this->p_string('url')
            );

            $height = $this->p_int('height');

            $img = $this->getFiles('img');

            $this->load('Banners', 'admin')->edit($id, $params, $img, $height);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }else{
            $ret = array(
                'getEdit' => $this->load('Banners', 'admin')->getOne($id)
            );

            return $this->view($ret);
        }
    }

    public function removeAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $this->load('Banners', 'admin')->remove($this->p_int('id'));

        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index'))
        );
    }

    public function validatorAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'title' => $this->p_string('title'),
            'url' => $this->p_string('url'),
            'img' => $this->p_int('img')
        );

        $error = array();

        $validItem = $this->load('Validator')->validStringLength($params['title'], 0, 50);
        if($validItem == false){
            $error['title'] = $validItem;
        }

        $validItem = $this->load('Validator')->validStringLength($params['url'], 3, 200);
        if($validItem == false){
            $error['url'] = $validItem;
        }

        if($params['img'] == 0){
            $error['img'] = false;
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );

        return $this->json($ret);
    }
}