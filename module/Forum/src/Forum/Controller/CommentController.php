<?php

/**
 * Description of GroupController
 * @author Andrew
 */
namespace Forum\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Forum\Model;
use Forum\Model\Obj\CommentObj;
use Forum\Model\Obj\VoteObj;
use Forum\Form\GroupForm;
use Forum\Form\Filter;
use Zend\Validator\File\Size;
class CommentController extends \Forum\Base\Controller
{
     
    public function addAction(){
        if($this->getRequest()->isXmlHttpRequest()){
            $params = $this->params()->fromPost();
            $params['id_user'] = $this->session->auth['id'];
            $params['creation'] = date('Y-m-d m:h:s');
//            $params['status'] = 1; // view 0 - hide
            if(empty($params['vote']))
                $params['vote'] = 0;
            $modelComment = new CommentObj();
            $modelComment ->setData($params);
            $modelComment ->save();
            $commentData = $modelComment ->getLastInsertData();
            
        }
        
        $view = new ViewModel();
        $view->setTerminal(true);
        $view->setVariable('commentData',$commentData);
        return $view;
    }
    public function voteAction(){
        if($this->getRequest()->isXmlHttpRequest()){
            $params = $this->params()->fromPost();
            $params['id_user'] = $this->session->auth['id'];
            $params['creation'] = date('Y-m-d m:h:s');
//            $params['status'] = 1; // view 0 - hide
            if(empty($params['vote']))
                $params['vote'] = 0;
            $modelComment = new VoteObj();
            $modelComment ->setData($params);
            $modelComment ->save();
            $commentData = $modelComment ->getLastInsertData();
            
        }
        
        $view = new ViewModel();
        $view->setTerminal(true);
        $view->setVariable('commentData',$commentData);
        return $view;
    }
}