<?php
namespace Application\Controller;

class CronController extends \Base\Mvc\Controller
{
    public function dailyAction()
    {
      /** TODO: Example
        $this->load('SendEmail', 'admin')->expiryTime(1);
        $this->load('SendEmail', 'admin')->expiryTime(3);
        $this->load('SendEmail', 'admin')->expiryTime(7);
      */
        $this->load('Forgot', 'profile')->clearKeyTemp(0);
        die();
    }
}