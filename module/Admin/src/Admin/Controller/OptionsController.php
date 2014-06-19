<?php
namespace Admin\Controller;

class OptionsController extends \Admin\Base\Controller
{
    public function __construct()
    {
        parent::__construct();

        $ret = array(
            'categoryList' => $this->load('AdvertCategory', 'admin')->get()
        );

        $this->pushView($ret);
    }

    public function indexAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $params = array(
            'category' => $this->p_int('category'),
            'type' => $this->p_int('type'),
            'page' => $this->p_int('page', 1)
        );

        $ret = array(
            'itemsList' => $this->load('Options', 'admin')->getList($params),
            'paginator' => $this->load('Options', 'admin')->getPaginator($params)
        );

        return $this->view($ret);
    }

    public function addAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if($this->p_int('add-form') === 1){

            $this->load('Options', 'admin')->add($this->setParams());

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index')));
        }

        $ret = array(
            'getCategory' => $this->load('AdvertCategory', 'admin')->get()
        );

        return $this->view($ret);
    }

    public function editAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');

        if($this->p_int('edit-form') === 1){

            $this->load('Options', 'admin')->edit($this->setParams(), $id);

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index')));
        }

        $ret = array(
            'getCategory' => $this->load('AdvertCategory', 'admin')->get(),
            'getEdit' => $this->load('Options', 'admin')->getOne($id)
        );

        return $this->view($ret);
    }

    public function removeAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->load('Options', 'admin')->remove($this->p_int('id'));

        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index', 'id' => null), array(), true)
        );
    }

    public function validatorAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $params = array(
            'name' => $this->p_string('name'),
            'option_name' => $this->p_array('option_name'),
            'valueArray' => $this->p_array('valueArray'),
            'type' => $this->p_string('type')
        );

        $error = array();


        $validItem = $this->load('Validator')->validStringLength($params['name'], 2, 50);
        if ($validItem == false) {
            $error['name'] = $validItem;
        }
        if ($params['type'] == 'radio' || $params['type'] == 'select' || $params['type'] == 'multi') {
            foreach ($params['valueArray'] as $key => $item) {
                $validItem = $this->load('Validator')->validStringLength($item['name'], 1, 50);
                if ($validItem == false) {
                    $error['valueArray'][$key]['name'] = $validItem;
                }
            }
        }

        $ret = array(
            'status' => (sizeof($error) > 0 ? false : true),
            'error' => $error
        );

        return $this->json($ret);
    }

    /**
     * Set params
     * @return array
     */
    private function setParams(){
        $params = array(
            'name' => $this->p_string('name'),
            'type' => $this->p_string('type'),
            'category_id' => $this->p_int('category_id'),
            'default' => $this->p_string('default'),
            'option' => $this->p_array('option')
        );



        if (!empty($params['option'])) {
            foreach ($params['option'] as &$item) {
                $item['selected'] = isset($item['selected']) ? 'y' : 'n';
                $item['value'] = $item['name'];
            }
        }
        if($params['type'] == 'checkbox'){
            $params['default'] = (isset($params['default']) && $params['default'] == 'y') ? 'y' : 'n';
        }
        return $params;
    }

}