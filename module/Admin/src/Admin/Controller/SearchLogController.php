<?php
namespace Admin\Controller;

class SearchLogController extends \Admin\Base\Controller
{
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $page = $this->p_int('page', 1);

        $ret = array(
            'logList' => $this->load('SearchLog', 'admin')->getList($page),
            'paginator' => $this->load('SearchLog', 'admin')->getPaginator($page)
        );

        return $this->view($ret);
    }
}