<?php
namespace Application\Controller;

class ImageController extends \Base\Mvc\Controller 
{    
    public function advertsGalleryAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $url = $this->load('AdvertGallery', 'admin')->one($id);

        return $this->getByParams($url);
    }

    public function userAvatarAction(){
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $url = $this->load('Users', 'admin')->getAvatar($id);

        return $this->getByParams($url);
    }

    private function getByParams($url)
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $w = $this->p_int('w');
        $h = $this->p_int('h');
        $crop = $this->p_select('crop', 'n', array('y', 'n'));

        return $this->load('Image')->get($url, $w, $h, $crop);
    }
}
