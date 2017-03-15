<?php

namespace Tests\Helpers;

use Contributte\Database\Transaction\Transaction;
use Nette\Database\Context;
use Nette\Database\SqlLiteral;
use Nette\Database\Table\Selection;
use Tester\Assert;
use Tester\Environment;
use Tester\TestCase;

abstract class BaseTestCase extends TestCase
{

	/** @var Context */
	protected $db;

	/** @var Transaction */
	protected $transaction;

	/**
	 * Called before test method
	 *
	 * @return void
	 */
	protected function setUp()
	{
		Environment::lock('database', TMP_DIR);
		$this->db = DatabaseFactory::create();
		$this->transaction = new Transaction($this->db->getConnection());
	}

	/**
	 * Called after test method
	 *
	 * @return void
	 */
	protected function tearDown()
	{
		$this->db->query('TRUNCATE TABLE `?`', new SqlLiteral('test'));
		$this->transaction = NULL;
	}

	/**
	 * @param int $count
	 * @return void
	 */
	protected function validateCount($count)
	{
		$records = $this->table()->count('id');
		Assert::equal($count, $records);
	}

	/**
	 * @return Selection
	 */
	protected function table()
	{
		return $this->db->table('test');
	}

}
