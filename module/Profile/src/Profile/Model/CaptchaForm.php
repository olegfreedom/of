<?php
namespace Profile\Model;

use Zend\Form\Form,
    Zend\Form\Element\Captcha,
    Zend\Captcha\Image as CaptchaImage;

class CaptchaForm extends Form
{
    public function __construct()
    {
        parent::__construct('Form Captcha');
        $this->setAttribute('method', 'post');

        $dirData = './public';

        //pass captcha image options
        $captchaImage = new CaptchaImage(  array(
                'font' => $dirData.'/fonts/pfagorasanspro-thin.ttf',
                'width' => 250,
                'height' => 100,
                'dotNoiseLevel' => 0,
                'lineNoiseLevel' => 0,
                'wordLen' => 4)
        );
        $captchaImage->setImgDir($dirData.'/captcha');
        $captchaImage->setImgUrl('/captcha');

        //add captcha element...
        $this->add(array(
            'type' => 'Zend\Form\Element\Captcha',
            'name' => 'captcha',
            'class' => 'form-control',
            'options' => array(
                'captcha' => $captchaImage,
            ),
        ));

    }

}