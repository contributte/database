<?php

namespace Contributte\Database\Transaction;

use Contributte\Database\Exception\InvalidTransactionException;
use Contributte\Database\Exception\UnresolvedTransactionException;
use Exception;
use Nette\Database\Connection;
use PDO;

/**
 * @author Milan Felix Sulc <sulcmil@gmail.com>
 */
class Transaction
{

	/** @var array */
	public $onUnresolved = [];

	/** @var array */
	protected static $drivers = ['pgsql', 'mysql', 'mysqli', 'sqlite'];

	/** @var int */
	protected static $level = 0;

	/** @var UnresolvedTransactionException */
	protected $unresolved;

	/** @var Connection */
	protected $connection;

	/**
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->unresolved = new UnresolvedTransactionException();
	}

	/**
	 * Close and check unresolved transactions
	 */
	public function __destruct()
	{
		if (self::$level > 0) {
			foreach ($this->onUnresolved as $callback) {
				call_user_func_array($callback, [$this->unresolved]);
			}
		}
	}

	/**
	 * @return bool
	 */
	protected function isSupported()
	{
		return in_array($this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME), self::$drivers);
	}

	/**
	 * @return Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * Run transaction in function scope
	 *
	 * @param callable $callback
	 * @return void
	 * @throws Exception
	 */
	public function transaction(callable $callback)
	{
		$this->begin();
		try {
			$callback($this->connection);
			$this->commit();
		} catch (Exception $e) {
			$this->rollback();
			throw $e;
		}
	}

	/**
	 * @see self::transaction
	 * @param callable $callback
	 * @return void
	 * @throws Exception
	 */
	public function t(callable $callback)
	{
		$this->transaction($callback);
	}

	/**
	 * Begin transaction. Save current save point.
	 *
	 * @return void
	 */
	public function begin()
	{
		if (self::$level === 0 || !$this->isSupported()) {
			$this->connection->beginTransaction();
		} else {
			$this->connection->getPdo()->exec('SAVEPOINT LEVEL' . self::$level);
		}

		self::$level++;
	}

	/**
	 * Commit transaction. Release current save point.
	 *
	 * @return void
	 * @throws InvalidTransactionException
	 */
	public function commit()
	{
		if (self::$level === 0) {
			throw new InvalidTransactionException('No transaction started');
		}

		self::$level--;

		if (self::$level === 0 || !$this->isSupported()) {
			$this->connection->commit();
		} else {
			$this->connection->getPdo()->exec('RELEASE SAVEPOINT LEVEL' . self::$level);
		}
	}

	/**
	 * Rollback to savepoint.
	 *
	 * @return void
	 * @throws InvalidTransactionException
	 */
	public function rollback()
	{
		if (self::$level === 0) {
			throw new InvalidTransactionException('No transaction started');
		}

		self::$level--;

		if (self::$level === 0 || !$this->isSupported()) {
			$this->connection->rollBack();
		} else {
			$this->connection->getPdo()->exec('ROLLBACK TO SAVEPOINT LEVEL' . self::$level);
		}
	}

	/**
	 * FACTORY *****************************************************************
	 */

	/**
	 * @return Promise
	 */
	public function promise()
	{
		return new Promise($this);
	}

}
