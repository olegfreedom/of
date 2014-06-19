<?php
namespace Admin\Controller;

class SqlUpdateController extends \Admin\Base\Controller
{    
    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        $ret = array(
            'filesList' => $this->load('SqlUpdate', 'admin')->getList(),
            'scanDir' => $this->load('SqlUpdate', 'admin')->scanDir()
        );
        
        return $this->view($ret);
    }
    
    public function updateAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        
        $type = $this->p_select('type', 'update', array('update', 'ignore'));
        $files = $this->p_array('files');
        
        $this->load('SqlUpdate', 'admin')->setUpdate($type, $files);
        
        return $this->redirect()->toUrl(
            $this->easyUrl(array('action' => 'index'))
        );
    }
}
