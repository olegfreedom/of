<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Db\Sql;

use Zend\Db\Adapter\StatementContainerInterface;
use Zend\Db\Adapter\AdapterInterface;

abstract class AbstractPreparableSql extends AbstractSql implements PreparableSqlInterface
{
    /**
     * Prepare statement
     *
     * @param  AdapterInterface $adapter
     * @param  StatementContainerInterface $statementContainer
     * @return void
     */
    public function prepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer = null)
    {
        if ($this->lock) {
            $statementContainer = $this->processPrepareStatement($adapter, $statementContainer);
        } else {
            try {
                $this->lock = true;
                $statementContainer = $this->getSqlPlatform()
                                        ->setSubject($this)
                                        ->setPlatform($adapter)
                                        ->prepareStatement($adapter, $statementContainer);
                $this->lock = false;
            } catch (\Exception $e) {
                $this->lock = false;
                throw $e;
            }
        }
        return $statementContainer;
    }

    abstract protected function processPrepareStatement(AdapterInterface $adapter, StatementContainerInterface $statementContainer = null);

}
