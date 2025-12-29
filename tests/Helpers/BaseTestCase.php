<?php declare(strict_types = 1);

namespace Tests\Helpers;

use Contributte\Database\Transaction\Transaction;
use Contributte\Tester\Environment;
use Nette\Database\Context;
use Nette\Database\SqlLiteral;
use Nette\Database\Table\Selection;
use Tester\Assert;
use Tester\TestCase;

abstract class BaseTestCase extends TestCase
{

	protected Context $db;

	protected ?Transaction $transaction = null;

	/**
	 * Called before test method
	 */
	protected function setUp(): void
	{
		\Tester\Environment::lock('database', Environment::getTestDir());
		$this->db = DatabaseFactory::create();
		$this->transaction = new Transaction($this->db->getConnection());
	}

	/**
	 * Called after test method
	 */
	protected function tearDown(): void
	{
		$this->db->query('TRUNCATE TABLE `?`', new SqlLiteral('test'));
		$this->transaction = null;
	}

	protected function validateCount(int $count): void
	{
		$records = $this->table()->count('id');
		Assert::equal($count, $records);
	}

	protected function table(): Selection
	{
		return $this->db->table('test');
	}

}
