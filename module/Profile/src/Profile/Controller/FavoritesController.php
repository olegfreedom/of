<?php
namespace Profile\Controller;

class FavoritesController extends \Profile\Base\Controller
{
    public function indexAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $userId = $this->getUserId();

        $ret = array(
            'advertsList' => $this->load('Adverts', 'admin')->getList(array(), $userId, true),
        );

        return $this->view($ret);
    }

    public function removeAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);

        $id = $this->p_int('id');
        $userId = $this->getUserId();

        $this->load('Favorites', 'profile')->remove($id, $userId);

        return $this->redirect()->toUrl(
            $this->easyUrl(array('action' => 'index'))
        );
    }


    public function addFavoritesAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $advert = $this->p_int('advert');
        $userId = $this->getUserId();

        $id = $this->load('Favorites', 'profile')->add($advert, $userId);

        $ret = array(
            'status' => ($id > 0 ? true : false),
            'url' => $this->easyUrl(array('module' => 'profile', 'controller' => 'favorites', 'action' => 'remove-favorites', 'advert' => $advert, 'id' => $id)),
            'text' => 'Удалить из избранного',
            'addClass' => true,
        );

        return $this->json($ret);
    }

    public function removeFavoritesAction()
    {
        $this->log(__CLASS__ . '\\' . __FUNCTION__);
        $this->isAjax();

        $id = $this->p_int('id');
        $advert = $this->p_int('advert');
        $userId = $this->getUserId();

        $ret = array(
            'status' => $this->load('Favorites', 'profile')->remove($id, $userId),
            'url' => $this->easyUrl(array('module' => 'profile', 'controller' => 'favorites', 'action' => 'add-favorites', 'advert' => $advert)),
            'text' => 'Добавить в избранное',
            'addClass' => false,
        );

        return $this->json($ret);
    }
}