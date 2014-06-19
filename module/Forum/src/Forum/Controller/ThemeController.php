<?php

/**
 * Description of ThemeController
 *
 * @author Andrew
 */
namespace Forum\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Forum\Form\ThemeForm;
use Forum\Model\Obj\ThemeObj;
use Forum\Model\Obj\GroupObj;
class ThemeController extends \Forum\Base\Controller
{
    public function indexAction(){
        
    }
    public function listAction(){
        $idGroup = $this->params()->fromRoute('id_group');
        
        $modelGroup = new GroupObj();
        $groupData = $modelGroup ->getDataList(array('id' => $idGroup));

        $modelTheme = new ThemeObj();
        $themeList = $modelTheme ->getDataList(array('id_forum_group' => $idGroup));
        
        return $this->view(array(
            'themeList' => $themeList,
            'groupData' => $groupData[0],
        ));
    }
    
    public function addAction(){
        $form = new ThemeForm();

        if($this->getRequest()->isPost()){
            $params = $this->params()->fromPost();
            
            $params['id_forum_group'] = $this->params()->fromRoute('id_group');
            $params['id_forum_group'] = $this->params()->fromRoute('id_group');
                unset($params['id']);
            $params['creation'] = date('Y-m-d m:h:s');
            $params['id_user'] = $this->session->auth['id'];
            $modelGroup = new ThemeObj();
            $modelGroup ->setData($params);
            $modelGroup ->save();
            
            $this->redirect()->toRoute('forum/group-view',
                array(
                    'id_group'=>$params['id_forum_group'],
            ));
        }
        
        $formView = new ViewModel(array('form'=>$form));
        $formView ->setTemplate("form/theme");
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
                
        return $this->view(array(
            'form' =>  $viewRender->render($formView),
        ));
    }
    
    public function editAction(){
        $paramsRoute = $this->params()->fromRoute();
        $form = new ThemeForm();
        $modelTheme = new ThemeObj();
        $modelTheme ->initial(array('id'=>$paramsRoute['id_theme']));
        $themeData = $modelTheme ->getData();
        $form->populateValues($themeData);
         
        if($this->getRequest()->isPost()){
            $params = $this->params()->fromPost();
            
            $modelGroup = new ThemeObj();
            $modelGroup ->setData($params);
            $modelGroup ->update();
            
            $this->redirect()->toRoute('forum/group-view/theme-list',
                array(
                    'id_group'=>$paramsRoute['id_group'],
            ));
        }
        
        $formView = new ViewModel(array('form'=>$form));
        $formView ->setTemplate("form/theme");
        $viewRender = $this->getServiceLocator()->get('ViewRenderer');
                
        return $this->view(array(
            'form' =>  $viewRender->render($formView),
        ));
        
    }
    
    public function deleteAction(){
        // this id we must get
        $paramsRoute = $this->params()->fromRoute();
        $themeGroup = new ThemeObj();
        
        if($this->getRequest()->isPost() && $this->params()->fromPost('answer',false)){
            $themeGroup->setData(array('id' => $paramsRoute['id_theme']));
            $result = $themeGroup ->delete();
              $this->redirect()->toRoute('forum/group-view/theme-list',
                array(
                    'id_group'=>$paramsRoute['id_group'],
            ));
        }
        $themeGroup ->initial(array('id'=>$paramsRoute['id_theme']));
        $themeData = $themeGroup ->getData();

        return $this->view(array(
            'groupData' => $themeData,
        ));
    }
}
