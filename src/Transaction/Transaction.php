<?php declare(strict_types = 1);

namespace Contributte\Database\Transaction;

use Contributte\Database\Exception\InvalidTransactionException;
use Contributte\Database\Exception\UnresolvedTransactionException;
use Exception;
use Nette\Database\Connection;
use PDO;
use Throwable;

class Transaction
{

	/** @var callable[] */
	public $onUnresolved = [];

	/** @var string[] */
	protected static $drivers = ['pgsql', 'mysql', 'mysqli', 'sqlite'];

	/** @var int */
	protected static $level = 0;

	/** @var UnresolvedTransactionException */
	protected $unresolved;

	/** @var Connection */
	protected $connection;

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

	protected function isSupported(): bool
	{
		return in_array($this->connection->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME), self::$drivers, true);
	}

	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * API *********************************************************************
	 */

	/**
	 * Run transaction in function scope
	 *
	 * @throws Exception
	 */
	public function transaction(callable $callback): void
	{
		$this->begin();
		try {
			$callback($this->connection);
			$this->commit();
		} catch (Throwable $e) {
			$this->rollback();
			throw $e;
		}
	}

	/**
	 * @see self::transaction
	 * @throws Exception
	 */
	public function t(callable $callback): void
	{
		$this->transaction($callback);
	}

	/**
	 * Begin transaction. Save current save point.
	 */
	public function begin(): void
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
	 * @throws InvalidTransactionException
	 */
	public function commit(): void
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
	 * @throws InvalidTransactionException
	 */
	public function rollback(): void
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

	public function promise(): Promise
	{
		return new Promise($this);
	}

}
