<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Db\Sql\Platform;


class PlatformDecoratorWraper implements PlatformDecoratorInterface
{
    /**
     * @var integer
     */
    protected $nestedCount = -1;

    protected $decorator = null;

    protected $subject = null;

    public function __construct($decorator)
    {
        $this->decorator = is_string($decorator) ? new $decorator : $decorator;
    }

    public function isInstanceOf($instanceOf)
    {
        return $this->decorator instanceof $instanceOf;
    }

    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    public function __call($method, $arguments)
    {
        $this->nestedCount++;
        if ($this->nestedCount !== 0) {
            $decorator = clone $this->decorator;
        } else {
            $decorator = $this->decorator;
        }
        $decorator->setSubject($this->subject);

        $result = call_user_func_array(array($decorator,$method), $arguments);

        $this->nestedCount--;
        return $result;
    }
}
