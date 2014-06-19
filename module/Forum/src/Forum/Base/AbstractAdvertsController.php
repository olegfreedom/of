<?php
namespace Forum\Base;

abstract class AbstractAdvertsController extends Controller {

    public function loadOptionsAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'advert_id' => $this->p_int('advert_id'),
            'category' => $this->p_int('category')
        );

        $ret = array(
            'optionsList' => $this->load('Options', 'admin')->getList($params, true),
        );

        return $this->json($ret);
    }

    public function getFieldsAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $categoryId = $this->p_int('category_id');

        $ret = $this->load('RequireParams', 'admin')->getOne($categoryId);

        return $this->json($ret);
    }

    public function getCityAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $region_id = $this->p_int('region');

        $ret = array(
            'citiesList' => $this->load('AdvertLocation', 'admin')->getCitiesByRegion($region_id)
        );
        return $this->json($ret);
    }
}