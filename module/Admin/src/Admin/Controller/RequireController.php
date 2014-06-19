<?php
namespace Admin\Controller;

class RequireController extends \Admin\Base\Controller
{
    public function indexAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $res = array(
            'paramsList' => $this->load('RequireParams', 'admin')->getList(),
        );

        return $this->view($res);
    }

    public function addAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        if ($this->p_int('add-form') === 1){
            $category_id = $this->p_int('category_id');
            $params = $this->p_array('params');

            $this->load('RequireParams', 'admin')->add($category_id, array_keys($params));

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }

        $res = array(
            'getCategory' => $this->load('AdvertCategory', 'admin')->get()
        );

        return $this->view($res);
    }

    public function editAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');

        if ($this->p_int('edit-form') === 1){
            $category_id = $this->p_int('category_id');
            $params = $this->p_array('params');

            $this->load('RequireParams', 'admin')->add($category_id, array_keys($params));

            return $this->redirect()->toUrl(
                $this->easyUrl(array('action'=>'index'))
            );
        }

        $res = array(
            'getCategory' => $this->load('AdvertCategory', 'admin')->get(),
            'getEdit' => $this->load('RequireParams', 'admin')->getOne($id)
        );

        return $this->view($res);
    }

    public function removeAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $this->load('RequireParams', 'admin')->remove($this->p_int('id'));

        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index', 'id' => null), array(), true)
        );
    }

    public function getFieldsAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $categoryId = $this->p_int('category_id');

        $ret = $this->load('RequireParams', 'admin')->getOne($categoryId);

        return $this->json($ret);
    }
}