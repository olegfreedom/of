<?php
namespace Application\Controller;

/**
 * Created by PhpStorm.
 * User: work
 * Date: 3/31/14
 * Time: 10:31 AM
 */
class SystemController extends \Application\Base\Controller
{
    public function __construct(){
        parent::__construct();

        $this->pushTitle('Сервисные функции');

    }

    public function importLocationsAction(){
        $this->log(__CLASS__.'\\'.__FUNCTION__);

        $cntAr = $this->load('System')->importLocations();

        $this->debug($cntAr);

        die();
// die('Количество локаций:' . $cntLocations);
        /*
        $params = array(
            'cntLocations' => $cntLocations
        );
        */

        // return $this->view($params);
    }

    public function testAction()
    {
        // $Url = new \Zend\Mvc\Controller\Plugin\Url();

        // $Url->setController($this);


        var_dump($_SESSION['profile']->auth);
        die();
        $this->load('System')->test();
        die();


        // var_dump($Url->getController());die();
        // $this->debug($Url->getController());
    }

    public function testerAction()
    {

    }
    public function aboutAction()
    {

    }
    public function howAction()
    {

    }
    public function categoryAction()
    {

    }
    public function territoryAction()
    {

    }
    public function timeAction()
    {

    }
    public function priceAction()
    {

    }
    public function sellprodAction()
    {

    }
    public function sellserviceAction()
    {

    }
    public function sellinvestmentAction()
    {

    }
    public function selltechnologyAction()
    {

    }
    public function buyprodAction()
    {

    }
    public function buyserviceAction()
    {

    }
    public function buyinvestmentAction()
    {

    }
    public function buytechnologyAction()
    {

    }
    public function progrAction()
    {

    }
    public function conceptsAction()
    {

    }
    public function bizAction()
    {

    }
    public function cooperateAction()
    {

    }
    public function askAction()
    {

    }
    public function scienceAction()
    {

    }
    public function coopworldAction()
    {

    }
    public function blogsAction()
    {

    }

}
