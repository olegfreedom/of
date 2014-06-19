<?php
namespace Admin\Controller;

class LocationsController extends \Admin\Base\Controller
{
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $ret = array(
            'paramsList' => $this->load('AdvertLocation', 'admin')->getRegions()
        );

        return $this->view($ret);
    }

    public function addRegionAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($this->p_int('add-form')) {

            $params = array(
                'name' => $this->p_string('name')
            );

            $this->load('AdvertLocation', 'admin')->addRegion($params);

            $this->redirect()->toUrl($this->easyUrl(array('action' => 'index')));

        } else {

            $ret = array();

            return $this->view($ret);
        }
    }

    public function editRegionAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');

        if ($this->p_int('edit-form')) {

            $params = array(
                'name' => $this->p_string('name')
            );

            $this->load('AdvertLocation', 'admin')->editRegion($id, $params);

            $this->redirect()->toUrl($this->easyUrl(array('action' => 'index')));

        } else {

            $ret = array(
                'getOne' => $this->load('AdvertLocation', 'admin')->getOneRegion($id)
            );

            return $this->view($ret);
        }
    }

    public function removeRegionAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id =$this->p_int('id');
        $error = $this->load('AdvertLocation', 'admin')->checkKeysRegion($id);
        if($error == true){
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'error-region'))
            );
        }else{
            $this->load('AdvertLocation', 'admin')->removeRegion($id);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }
    }

    public function errorRegionAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = array();

        return $this->view($ret);
    }

    public function cityAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $ret = array(
            'paramsList' => $this->load('AdvertLocation', 'admin')->get()
        );

        return $this->view($ret);
    }

    public function cityAddAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($this->p_int('add-form')) {

            $params = array(
                'name' => $this->p_string('name'),
                'region_id' => $this->p_int('region_id')
            );

            $this->load('AdvertLocation', 'admin')->add($params);

            $this->redirect()->toUrl($this->easyUrl(array('action' => 'city')));

        } else {

            $ret = array(
                'regionList' => $this->load('AdvertLocation', 'admin')->getRegions()
            );

            return $this->view($ret);
        }
    }

    public function cityEditAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');

        if ($this->p_int('edit-form')) {

            $params = array(
                'name' => $this->p_string('name'),
                'region_id' => $this->p_int('region_id')
            );

            $this->load('AdvertLocation', 'admin')->edit($id, $params);

            $this->redirect()->toUrl($this->easyUrl(array('action' => 'city')));

        } else {

            $ret = array(
                'getOne' => $this->load('AdvertLocation', 'admin')->getOne($id),
                'regionList' => $this->load('AdvertLocation', 'admin')->getRegions()
            );

            return $this->view($ret);
        }
    }

    public function cityRemoveAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id =$this->p_int('id');
        $error = $this->load('AdvertLocation', 'admin')->checkKeys($id);
        if($error == true){
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'error-region'))
            );
        }else{
            $this->load('AdvertLocation', 'admin')->remove($id);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }
    }

    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'name' => $this->p_string('name')
        );

        $error = array();

        $validItem = $this->load('Validator')->validStringLength($params['name'], 2, 100);
        if($validItem == false){
            $error['name'] = $validItem;
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );

        return $this->json($ret);
    }
}