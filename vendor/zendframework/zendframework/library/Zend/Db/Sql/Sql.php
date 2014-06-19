<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Db\Sql;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Adapter\Driver\StatementInterface;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Sql\Exception;

class Sql
{
    /** @var AdapterInterface */
    protected $adapter = null;

    /** @var PlatformInterface */
    protected $adapterPlatform = null;

    /** @var string|array|TableIdentifier */
    protected $table = null;

    /**
     * Construct sql object.
     * You can exchange $table and $adapter for backward compatibility purposes.
     *
     * @param string $table
     * @param AdapterInterface $adapter
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($table = null, $adapter = null)
    {
        if ($table instanceof AdapterInterface) {
            // backward compatibility - shoud be deprecated
            list($table, $adapter) = array($adapter, $table);
        }
        if ($table) {
            $this->setTable($table);
        }
        if ($adapter) {
            if (!$adapter instanceof AdapterInterface) {
                throw new Exception\InvalidArgumentException("adapter shoud be instanceof AdapterInterface");
            }
            $this->adapter = $adapter;
            $this->adapterPlatform = $adapter->getPlatform();
        }
    }

    /**
     * @return null|AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     *
     * @return bool
     */
    public function hasTable()
    {
        return ($this->table != null);
    }

    /**
     * Set default table for statements
     *
     * @param string|array|\Zend\Db\Sql\TableIdentifier $table
     * @return self
     * @throws Exception\InvalidArgumentException
     */
    public function setTable($table)
    {
        if (is_string($table) || is_array($table) || $table instanceof TableIdentifier) {
            $this->table = $table;
        } else {
            throw new Exception\InvalidArgumentException('Table must be a string, array or instance of TableIdentifier.');
        }
        return $this;
    }

    /**
     * Return default table for statements
     *
     * @return string|is_array|TableIdentifier
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Create Select statement
     *
     * @param string|is_array|TableIdentifier $table
     * @return Select
     */
    public function select($table = null)
    {
        return $this->createStatement(__FUNCTION__, $table);
    }

    /**
     * Create Insert statement
     *
     * @param string|is_array|TableIdentifier $table
     * @return Insert
     */
    public function insert($table = null)
    {
        return $this->createStatement(__FUNCTION__, $table);
    }

    /**
     * Create Update statement
     *
     * @param string|is_array|TableIdentifier $table
     * @return Update
     */
    public function update($table = null)
    {
        return $this->createStatement(__FUNCTION__, $table);
    }

    /**
     * Create Delete statement
     *
     * @param string|is_array|TableIdentifier $table
     * @return Delete
     */
    public function delete($table = null)
    {
        return $this->createStatement(__FUNCTION__, $table);
    }

    /**
     * Create statement
     *
     * @param string $queryType
     * @param string|is_array|TableIdentifier $table
     * @return \Zend\Db\Sql\SqlInterface
     * @throws Exception\InvalidArgumentException
     */
    protected function createStatement($queryType, $table)
    {
        if ($this->table !== null && $table !== null) {
            throw new Exception\InvalidArgumentException(sprintf(
                'This Sql object is intended to work with only the table "%s" provided at construction time.',
                $this->table
            ));
        }
        $queryType   = __NAMESPACE__ . '\\'. ucfirst($queryType);
        return new $queryType(($table) ?: $this->table);
    }

    /**
     * Convert sql statement object to platform specified string
     *
     * @param PreparableSqlInterface $sqlObject
     * @param StatementInterface|null $statement
     * @return StatementInterface
     * @todo we realy need a $statement parameter?
     */
    public function prepareStatementForSqlObject(PreparableSqlInterface $sqlObject, StatementInterface $statement = null)
    {
        return $sqlObject->prepareStatement($this->adapter, $statement);
    }

    /**
     * Convert sql statement object to platform specified string
     *
     * @param SqlInterface $sqlObject
     * @return string
     */
    public function getSqlStringForSqlObject(SqlInterface $sqlObject)
    {
        return $sqlObject->getSqlString($this->adapterPlatform);
    }
}
