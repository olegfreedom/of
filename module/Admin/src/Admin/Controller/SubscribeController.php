<?php
namespace Admin\Controller;

class SubscribeController extends \Admin\Base\Controller
{
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $page = $this->p_int('page', 1);

        $ret = array(
            'emailsList' => $this->load('SubscribeEmails')->getList($page),
            'paginator' => $this->load('SubscribeEmails')->getPaginator($page)
        );

        return $this->view($ret);
    }

    public function removeAction()
    {
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $this->load('SubscribeEmails')->remove($this->p_int('id'));

        return $this->redirect()->toUrl(
            $this->easyUrl(array('action'=>'index'))
        );
    }
}