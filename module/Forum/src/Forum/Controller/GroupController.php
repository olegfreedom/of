<?php

/**
 * Description of GroupController
 * @author Andrew
 */
namespace Forum\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Forum\Model;
use Forum\Model\Obj\GroupObj;
use Forum\Form\GroupForm;
use Forum\Form\Filter;
use Zend\Validator\File\Size;
class GroupController extends \Forum\Base\Controller
{
 
    public function listAction(){
        $modelGroup = new GroupObj();
        $groupList = $modelGroup ->getDataList();
        
        return $this->view(array(
            'groupList' => $groupList,
        ));
    }
    
    public function addAction(){
        
        $form = new GroupForm();
        if($this->getRequest()->isPost()){
            $params = $this->params()->fromPost();
                unset($params['id']);
            $file    = $this->params()->fromFiles('avatar');
            $fileUpload = new Model\Upload();
            $fileName = $fileUpload -> save($file);
                
            $modelGroup = new GroupObj();
                $params['avatar'] = $fileName;
                $params['creation'] = date('Y-m-d m:h:s');
                $params['id_user'] = $this->session->auth['id'];
                
            $modelGroup ->setData($params);
            $modelGroup ->save();
            $this->redirect()->toRoute('forum/default',
                array('controller'=>'group',
                      'action' => 'list'
            ));
        }
        
        $formView = new ViewModel(array('form'=>$form));
        $formView ->setTemplate("form/group");
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
                
        return $this->view(array(
            'form' =>  $viewRender->render($formView),
        ));
    }
    
    public function editAction(){
        $params = $this->params()->fromRoute();
        $form = new GroupForm();
        $modelGroup = new GroupObj();
        $modelGroup ->initial(array('id'=>$params['id']));
        $groupData = $modelGroup ->getData();
        $form->populateValues($groupData);
        
        if($this->getRequest()->isPost()){
            $params = $this->params()->fromPost();
            $modelGroup = new GroupObj();
                $params['last_work_date'] = date('Y-m-d m:h:s');
                $params['id_user'] = $this->session->auth['id'];
            $modelGroup ->setData($params);
            $modelGroup ->update();
        }
        
        $formView = new ViewModel(array('form'=>$form));
        $formView ->setTemplate("form/group");
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
        
        return $this->view(array(
            'form' => $viewRender->render($formView),
        ));
    }
    
    public function deleteAction(){
        // this id we must get
        $params = $this->params()->fromRoute();
        $modelGroup = new GroupObj();
        
        if($this->getRequest()->isPost() && $this->params()->fromPost('answer',false)){
            $modelGroup->setData(array('id' => $params['id']));
            $result = $modelGroup ->delete();
            $this->redirect()->toRoute('forum/default',
                array('controller'=>'group',
                      'action' => 'list')
            );
        }
        $modelGroup ->initial(array('id'=>$params['id']));
        $groupData = $modelGroup ->getData();

        return $this->view(array(
            'groupData' => $groupData,
        ));
    }
}