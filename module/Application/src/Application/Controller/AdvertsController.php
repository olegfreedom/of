<?php
namespace Application\Controller;

use Profile\Model\CaptchaForm;

class AdvertsController extends \Application\Base\Controller
{

    public function __construct(){
        parent::__construct();

        $this->pushTitle('Объявления');
    }

    public function indexAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);
        
        return $this->redirect()->toUrl(
            $this->easyUrl(array('controller' => 'catalog'))
        );
    }
    
    public function viewAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $id = $this->p_int('id');
        $advertsType = $this->p_int('type');
        $userId = $this->getUserId();

        if($id > 0){
            $this->load('AdvertCounter')->up($id);
            $ret = array(
                'currentUrl' => $this->getCurrentUrl(),
                'advert' => $this->load('Adverts', 'admin')->getOne($id, $userId),
                'getGallery' => $this->load('AdvertGallery', 'admin')->generateURL($id, $this),
                'getDates' => $this->load('AdvertDate', 'admin')->get(array('advert_id' => $id)),
                'userId' => $userId,
                'typeList' => $this->load('AdvertType', 'admin')->get(),
                'advertsType' => $advertsType,
                'advertComments' => $this->load('AdvertComment', 'admin')->getCommentsByAdvertId($id, $userId),
                'currentUser' => $this->load('Users', 'admin')->getNameAndUsername($userId),
                'captcha' => new CaptchaForm(),
                'user_fields' => $this->load('Adverts', 'admin')->getValueFromUserFields(9)
            );

            return $this->view($ret);
        }else{
            $this->indexAction();
        }
    }

}
