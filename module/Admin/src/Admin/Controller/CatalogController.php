<?php
namespace Admin\Controller;

class CatalogController extends \Admin\Base\Controller
{
    public function __construct() {
        parent::__construct();
        
        $ret = array(
            'categoryList' => $this->load('AdvertCategory', 'admin')->get()
        );

        $this->pushView($ret);
    }

    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $params = array(
            'category' => $this->p_int('category'),
            'type' => $this->p_int('type'),
            'user_id' => $this->p_int('user'),
            'page' => $this->p_int('page')
        );

        $ret = array(
            'itemsList' => $this->load('Adverts', 'admin')->getList($params),
            'paginator' => $this->load('Adverts', 'admin')->getPaginator($params)
        );
        return $this->view($ret);
    }
    
    public function addAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if($this->p_int('add-form') === 1){

            $params = $this->setParams();

            $arrays = $this->setArrays();

            $this->load('Adverts', 'admin')->add($params, $arrays);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );                 
        }else{            
            $ret = array(
                'getCategory' => $this->load('AdvertCategory', 'admin')->get(),
                'getType' => $this->load('AdvertType', 'admin')->get(),
                'getLocation' => $this->load('AdvertLocation', 'admin')->getRegions(),
                'getCurrency' => $this->load('AdvertCurrency', 'admin')->get(),
                'phoneMask' => $this->load('Phone', 'admin')->getPhoneMask(),
                'phonePlaceholder' => $this->load('Phone', 'admin')->getPlaceholder(),
                'phoneMaskArray' => $this->load('Phone', 'admin')->getMaskArray(),
                'usersList' => $this->load('Users', 'admin')->getAll(),
            );

            return $this->view($ret);
        }
    }
   
    public function editAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $id = $this->p_int('id');

        if($this->p_int('edit-form') === 1){

            $params = $this->setParams();


            if($this->p_int('up_advert') === 1){
                $params['timestamp'] = microtime(true);
            }

            if($this->p_int('prolong') === 1 && $this->load('Users', 'admin')->checkUserLevel($params['user_id'])){
                $this->load('Adverts', 'admin')->prolong($id);
            }

            $arrays = $this->setArrays();
            $this->load('Adverts', 'admin')->edit($id, $params, $arrays);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index', 'id' => null), array(), true)
            );
        }else{
            $getOne = $this->load('Adverts', 'admin')->getOne($id);

            $region = $this->load('AdvertLocation', 'admin')->getRegionByArea($getOne['location']);

            $ret = array(
                'getEdit' => $getOne,
                'getCategory' => $this->load('AdvertCategory', 'admin')->get(),
                'getType' => $this->load('AdvertType', 'admin')->get(),
                'getLocation' => $this->load('AdvertLocation', 'admin')->getRegions(),
                'region' => $region,
                'getCurrency' => $this->load('AdvertCurrency', 'admin')->get(),
                'getGallery' => $this->load('AdvertGallery', 'admin')->get($id),
                'getPhone' => $this->load('AdvertPhone', 'admin')->get($id),
                'phoneMask' => $this->load('Phone', 'admin')->getPhoneMask(),
                'phonePlaceholder' => $this->load('Phone', 'admin')->getPlaceholder(),
                'phoneMaskArray' => $this->load('Phone', 'admin')->getMaskArray(),
                'usersList' => $this->load('Users', 'admin')->getAll(),
            );
            return $this->view($ret);
        }
    } 
    
    public function removeAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
            
        $this->load('Adverts', 'admin')->remove($this->p_int('id'));

        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index', 'id' => null), array(), true)
        );                 
    }
    
    public function statusAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $advId = $this->p_int('id');
        $advType = $this->load('AdvertType', 'admin')->getTypeById($advId);

        $params = array(
            'advUrl' => $this->easyUrl(array('module' => 'application', 'controller' => 'adverts', 'action' => 'view', 'type' => $advType, 'id' => $advId)),
            'servicesUrl' => $this->easyUrl(array('module' => 'application','controller' => 'index', 'action' => 'index')).'#services'
        );
        
        
        $this->load('Adverts', 'admin')->setStatus($advId, $params);
        
        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index', 'id' => null), array(), true)
        );
    }
    
    public function userStatusAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->load('Users', 'admin')->setStatus($this->p_int('id'));
        
        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index', 'id' => null), array(), true)
        );
    }

    public function getCityAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $region_id = $this->p_int('region');

        $ret = array(
            'citiesList' => $this->load('AdvertLocation', 'admin')->getAreasByRegion($region_id)
        );
        return $this->json($ret);
    }
    
    public function validatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $category_id = $this->p_int('category_id');
        $requireParams = $this->load('RequireParams', 'admin')->getOne($category_id);

        $params = array(
            'name' => $this->p_string('name'),
            'phoneArray' => $this->p_array('phoneArray'),
            'description' => $this->p_string('description', '', false),
            'price' => $this->p_int('price')
        );
        
        $error = array();
                   
        $validItem = $this->load('Validator')->validStringLength($params['name'], 2, 50);
        if($validItem == false){
            $error['name'] = $validItem;
        }

        if ($requireParams && in_array('phone', $requireParams['fields'])) {
            foreach ($params['phoneArray'] as $key => $val) {
                    $validItem = $this->load('Phone', 'admin')->checkPhone($val);
                    if ($validItem == false) {
                        if (!isset($error['phoneArray'])) {
                            $error['phoneArray'] = array();
                        }
                        $error['phoneArray'][$key] = $validItem;
                    }
            }
        }

        if ($requireParams && in_array('description', $requireParams['fields'])) {
            $validItem = $this->load('Validator')->validStringLength($params['description'], 10, 20000);
            if ($validItem == false) {
                $error['description'] = $validItem;
            }
        }

        if ($requireParams && in_array('price', $requireParams['fields'])) {
            $validItem = $this->load('Validator')->validBetween($params['price'], 1, 9999999);
            if ($validItem == false) {
                $error['price'] = $validItem;
            }
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $this->json($ret);
    }
     
/*********************************************************************************
                                Category actions
 ********************************************************************************/
    
    public function categoryAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = array(
            'categoryList' => $this->load('AdvertCategory', 'admin')->get()
        );
        
        return $this->view($ret, 'admin/catalog/category/list.phtml');
    }
    
    public function categoryEditAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if($this->p_int('edit-form') === 1){
            $params = array(
                'name' => $this->p_string('name'),
                'parent_id' => $this->p_int('category_id', 1)
            );
            
            $this->load('AdvertCategory', 'admin')->edit($this->p_int('id'), $params);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'category'))
            );                 
        }else{                        
            $ret = array(
                'getEdit' => $this->load('AdvertCategory', 'admin')->getOne($this->p_int('id')),
                'categoryList' => $this->load('AdvertCategory', 'admin')->get()
            );

            return $this->view($ret, 'admin/catalog/category/edit.phtml');
        }
    }
    
    public function categoryAddAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if($this->p_int('add-form') === 1){
            $params = array(
                'name' => $this->p_string('name'),
                'parent_id' => $this->p_int('category_id', 1)
            );
            
            $this->load('AdvertCategory', 'admin')->add($params);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'category'))
            );                 
        }else{                        
            $ret = array(
                'categoryList' => $this->load('AdvertCategory', 'admin')->get()
            ); 

            return $this->view($ret, 'admin/catalog/category/add.phtml');
        }
    }
    
    public function categoryRemoveAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $id = $this->p_int('id');
        $error = $this->load('AdvertCategory', 'admin')->checkKeys($id);
        if($error == true){
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'category-error'))
            ); 
        }else{            
            $this->load('AdvertCategory', 'admin')->remove($id);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'category'))
            );                 
        }       
    }
    
    public function categoryErrorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
            
        return $this->view(null, 'admin/catalog/category/error.phtml');     
    }
    
    public function categoryValidatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'name' => $this->p_string('name')
        );
        
        $error = array();
        
        $validItem = $this->load('Validator')->validStringLength($params['name'], 1, 50);
        if($validItem == false){
            $error['name'] = $validItem;
        }
        
        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $this->json($ret);
    }
  
/*********************************************************************************
                                Type actions
 ********************************************************************************/
    
    public function typeAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = array(
            'typeList' => $this->load('AdvertType', 'admin')->get()
        );
        
        return $this->view($ret, 'admin/catalog/type/list.phtml');
    }
    
    public function typeEditAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if($this->p_int('edit-form') === 1){
            $params = array(
                'name' => $this->p_string('name')
            );
            
            $this->load('AdvertType', 'admin')->edit($this->p_int('id'), $params);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'type'))
            );                 
        }else{                        
            $ret = array(
                'getEdit' => $this->load('AdvertType', 'admin')->getOne($this->p_int('id'))
            );

            return $this->view($ret, 'admin/catalog/type/edit.phtml');
        }
    }
    
    public function typeAddAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if($this->p_int('add-form') === 1){
            $params = array(
                'name' => $this->p_string('name')
            );
            
            $this->load('AdvertType', 'admin')->add($params);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'type'))
            );                 
        }else{                        
            $ret = array();            

            return $this->view($ret, 'admin/catalog/type/add.phtml');
        }
    }
    
    public function typeRemoveAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $id =$this->p_int('id');
        $error = $this->load('AdvertType', 'admin')->checkKeys($id);
        if($error == true){
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'type-error'))
            ); 
        }else{            
            $this->load('AdvertType', 'admin')->remove($id);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'type'))
            );                 
        }       
    }
    
    public function typeErrorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
            
        return $this->view(null, 'admin/catalog/type/error.phtml');     
    }
    
    public function typeValidatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'name' => $this->p_string('name')
        );
        
        $error = array();
        
        $validItem = $this->load('Validator')->validStringLength($params['name'], 1, 10);
        if($validItem == false){
            $error['name'] = $validItem;
        }
        
        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $this->json($ret);
    }
 
/*********************************************************************************
                                Currency actions
 ********************************************************************************/
    
    public function currencyAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $ret = array(
            'currencyList' => $this->load('AdvertCurrency', 'admin')->get()
        );
        
        return $this->view($ret, 'admin/catalog/currency/list.phtml');
    }
    
    public function currencyEditAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if($this->p_int('edit-form') === 1){
            $params = array(
                'name' => $this->p_string('name')
            );
            
            $this->load('AdvertCurrency', 'admin')->edit($this->p_int('id'), $params);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'currency'))
            );                 
        }else{                        
            $ret = array(
                'getEdit' => $this->load('AdvertCurrency', 'admin')->getOne($this->p_int('id'))
            );

            return $this->view($ret, 'admin/catalog/currency/edit.phtml');
        }
    }
    
    public function currencyAddAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        if($this->p_int('add-form') === 1){
            $params = array(
                'name' => $this->p_string('name')
            );
            
            $this->load('AdvertCurrency', 'admin')->add($params);
            
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'currency'))
            );                 
        }else{                        
            $ret = array();

            return $this->view($ret, 'admin/catalog/currency/add.phtml');
        }
    }
    
    public function currencyRemoveAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
            
        $id =$this->p_int('id');
        $error = $this->load('AdvertCurrency', 'admin')->checkKeys($id);
        if($error == true){
            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'currency-error'))
            ); 
        }else{            
            $this->load('AdvertCurrency', 'admin')->remove($id);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'currency'))
            );                 
        }       
    }
    
    public function currencyErrorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
            
        return $this->view(null, 'admin/catalog/currency/error.phtml');     
    }
    
    public function currencyValidatorAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $params = array(
            'name' => $this->p_string('name')
        );
        
        $error = array();
        
        $validItem = $this->load('Validator')->validStringLength($params['name'], 1, 10);
        if($validItem == false){
            $error['name'] = $validItem;
        }
        
        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );
        
        return $this->json($ret);
    }
    
/*********************************************************************************
                                Gallery actions
 ********************************************************************************/
    
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

 /*********************************************************************************
                                Phone actions
 ********************************************************************************/
    
    public function removePhoneAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();
        
        $id = $this->p_int('id');
        $advert = $this->p_int('advert');
        
        $ret = array(
            'status' => $this->load('AdvertPhone', 'admin')->remove($advert, $id)
        );
        
        return $this->json($ret);
    }

    /*********************************************************************************
                                Options actions
     ********************************************************************************/

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

    /**
     * set params to array
     * @return array
     */
    private function setParams()
    {
        $params = array(
            'category' => $this->p_int('category_id'),
            'type' => $this->p_int('type_id'),
            'location' => $this->p_int('location'),
            'name' => $this->p_string('name'),
            'description_short' => $this->p_string('description_short', '', false),
            'description_full' => $this->p_string('description_full', '', false),
            'price' => $this->p_int('price'),
            'currency' => $this->p_int('currency'),
            'status' => $this->p_select('status', 'n', array('y', 'n')),
            'user_id' => $this->p_int('user_id', 1),
            'unit_type' => 4,
        );
        return $params;
    }

    /**
     * set other params for advert
     * @return array
     */
    private function setArrays()
    {
        $arrays = array(
            'gallery' => $this->getFiles('gallery'),
            'phone' => $this->p_array('phone'),
            'mask' => $this->p_array('mask'),
            'options' => $this->p_array('option')
        );

        foreach($arrays['options'] as &$option){
            if(is_array($option)){
                if(isset($option['checkbox_one'])){
                    $checkboxOne = $option['checkbox_one'];
                    $option = (isset($checkboxOne['hidden']) && !isset($checkboxOne['checkbox'])) ? $checkboxOne['hidden'] : $checkboxOne['checkbox'];
                }else if(isset($option['checkbox_multi'])){
                    $checkboxMulti = $option['checkbox_multi'];
                    $option = $this->jsonEncode($checkboxMulti);
                }
            }
        }
        return $arrays;
    }
}
