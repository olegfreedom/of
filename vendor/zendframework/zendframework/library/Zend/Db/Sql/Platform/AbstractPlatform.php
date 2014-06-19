<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Db\Sql\Platform;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Adapter\StatementContainerInterface;
use Zend\Db\Sql\PreparableSqlInterface;
use Zend\Db\Sql\SqlInterface;
use Zend\Db\Sql\ExpressionInterface;
use Zend\Db\Sql\Platform\PlatformDecoratorWraper;

class AbstractPlatform implements PreparableSqlInterface, SqlInterface, ExpressionInterface
{
    /**
     * @var object
     */
    protected $subject = null;

    /**
     *
     * @var object
     */
    protected $platform = null;

    /**
     * @var array of PlatformDecoratorInterface[]
     */
    protected $decorators = array(
        'mysql'     => array(
            'Zend\Db\Sql\Select' => 'Zend\Db\Sql\Platform\Mysql\SelectDecorator',
            'Zend\Db\Sql\Ddl\CreateTable' => 'Zend\Db\Sql\Platform\Mysql\Ddl\CreateTableDecorator',
        ),
        'sqlserver' => array(
            'Zend\Db\Sql\Select' => 'Zend\Db\Sql\Platform\SqlServer\SelectDecorator'
        ),
        'oracle'    => array(
            'Zend\Db\Sql\Select' => 'Zend\Db\Sql\Platform\Oracle\SelectDecorator'
        ),
    );

    /**
     * Set decorator for specified platform
     *
     * @param string|\Zend\Db\Adapter\Adapter|\Zend\Db\Adapter\Platform\PlatformInterface $platform
     * @param string $type
     * @param string|\Zend\Db\Sql\Platform\PlatformDecoratorInterface $decorator
     * @return self
     */
    public function setDecorator($platform, $type, $decorator)
    {
        $platformName = $this->resolvePlatformName($platform);
        $this->decorators[$platformName][$type] = $decorator;
        return $this;
    }

    /**
     * Add decorators for specified platform
     * @param array $decorators
     * @return \Zend\Db\Sql\Platform\AbstractPlatform
     */
    public function addDecorators($decorators)
    {
        foreach ($decorators as $platform=>$platformDecorators) {
            foreach ($platformDecorators as $type=>$decorator) {
                $this->setDecorator($platform, $type, $decorator);
            }
        }
        return $this;
    }

    /**
     * Find decorator for subject and platform. If not found - return subject
     *
     * @param string $instanceOf
     * @return PreparableSqlInterface|SqlInterface|ExpressionInterface
     */
    protected function getDecorator($instanceOf)
    {
        $platformName = $this->resolvePlatformName($this->platform);
        if (isset($this->decorators[$platformName])) {
            foreach ($this->decorators[$platformName] as $type => &$decorator) {
                if (is_string($decorator) || !$decorator instanceof PlatformDecoratorWraper) {
                    $decorator = new PlatformDecoratorWraper($decorator);
                }
                if ($this->subject instanceof $type && $decorator->isInstanceOf($instanceOf)) {
                    return $decorator->setSubject($this->subject);
                }
            }
        }
        return $this->subject;
    }

    /**
     * Resolve platform name
     *
     * @param string|\Zend\Db\Adapter\Adapter|\Zend\Db\Adapter\Platform\PlatformInterface $platform
     * @return string
     */
    protected function resolvePlatformName($platform)
    {
        if ($platform instanceof AdapterInterface) {
            $platform = $platform->getPlatform()->getName();
        } elseif ($platform instanceof PlatformInterface) {
            $platform = $platform->getName();
        }
        return strtolower($platform);
    }

    /**
     *
     * @param mixed $subject
     * @return self
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     *
     * @param mixed $platform
     * @return self
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;
        return $this;
    }

    /**
     * Return expression data for subject by platform
     */
    public function getExpressionData()
    {
        $decorator = $this->getDecorator('Zend\Db\Sql\ExpressionInterface');
        return $decorator->getExpressionData();
    }

    /**
     * Get SQL string for subject by platform
     *
     * @param  null|PlatformInterface $adapterPlatform If null, defaults to Sql92
     * @return string
     */
    public function getSqlString(PlatformInterface $platform = null)
    {
        $decorator = $this->getDecorator('Zend\Db\Sql\SqlInterface');
        return $decorator->getSqlString($platform);
    }

    /**
     * Prepare statement for subject by platform
     *
     * @param  AdapterInterface $adapter
     * @param  StatementContainerInterface $statementContainer
     * @return void
     */
    public function prepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer = null)
    {
        $decorator = $this->getDecorator('Zend\Db\Sql\SqlInterface');
        return $decorator->prepareStatement($adapter, $statementContainer);
    }
}
