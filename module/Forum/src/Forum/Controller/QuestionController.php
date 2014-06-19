<?php

/**
 * Description of ThemeController
 *
 * @author Andrew
 */
namespace Forum\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Forum\Model\Obj\GroupObj;
use Forum\Model\Obj\ThemeObj;
use Forum\Model\Obj\QuestionObj;
class QuestionController extends \Forum\Base\Controller
{
    public function indexAction(){
        
    }
    public function listAction(){
        // get data theme
        // get data group
        
        $paramsRoute = $this->params()->fromRoute();
  
        $modelGroup = new GroupObj();
        $groupData = $modelGroup ->getDataList(array('id' => $paramsRoute['id_group']));
//            $this->debug($groupData);
        $modelTheme = new ThemeObj();
        $modelTheme ->initial(array('id' => $paramsRoute['id_theme']));
        $themeData = $modelTheme->getData();
        
        $modelQuestion = new QuestionObj();
        $questionDataList = $modelQuestion->getDataList(array('id_theme'=>$paramsRoute['id_theme']));
//        \Zend\Debug\Debug::dump($questionDataList);
        return $this->view(array(
            'groupData' => $groupData[0],
            'themeData' => $themeData,
            'questionDataList' => $questionDataList,
        ));
    }
    
    public function addAction(){
        if($this->getRequest()->isXmlHttpRequest()){
            $params = $this->params()->fromPost();
            $params['id_user'] = $this->session->auth['id'];
            $params['creation'] = date('Y-m-d m:h:s');
            $params['status'] = 1; // view 0 - hide
            $modelQuestion = new QuestionObj(); 
            $modelQuestion ->setData($params);
            $modelQuestion ->save();
            $questionData = $modelQuestion ->getLastInsertData();
            $questionData['itemNum'] = $modelQuestion -> getCount(array('id_theme' => $params['id_theme']));
        }
//        return $this->json(array('aa' => '22'),array(
//                    'enableJsonExprFinder' => true,
//                    'keepLayouts'          => false,
//                ));
        
//        $this->_helper->viewRenderer->setNoRender(true);

        $view = new ViewModel();
        $view->setTerminal(true);
        $view->setVariable('questionData',$questionData);
        return $view;
    }
    
    public function editAction(){
        
    }
    
    public function deleteAction(){
        // this id we must get
        if($this->getRequest()->isXmlHttpRequest()){
            $paramsPost = $this->params()->fromPost();
            $questionObj = new QuestionObj();
            $questionObj->setData(array('id' => (int)$paramsPost['id']));
            $result = $questionObj ->delete();
        }
        
        return $this->json(array('result' => 'OK'),array(
            'enableJsonExprFinder' => true,
            'keepLayouts'          => false,
        ));
    }
}
