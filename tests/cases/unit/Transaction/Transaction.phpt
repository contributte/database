<?php

/**
 * Test: Contributte\Database\Transaction\Transaction
 *
 * @testCase
 */

namespace Tests\Cases\Unit\Transaction;

use Contributte\Database\Exception\InvalidTransactionException;
use Nette\Database\DriverException;
use Tester\Assert;
use Tests\Helpers\BaseTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

final class TransactionTest extends BaseTestCase
{

	/**
	 * @test
	 * @return void
	 */
	public function testCommit()
	{
		$this->validateCount(0);

		$this->transaction->begin();
		$this->table()->insert(['text' => time()]);
		$this->transaction->commit();

		$this->validateCount(1);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testRollback()
	{
		$this->validateCount(0);

		$this->transaction->begin();
		$this->table()->insert(['text' => time()]);

		$this->validateCount(1);
		$this->transaction->rollback();

		$this->validateCount(0);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testCommitWithoutBegin()
	{
		Assert::exception(function () {
			$this->transaction->commit();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testRollbackWithoutBegin()
	{
		Assert::exception(function () {
			$this->transaction->rollback();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testDoubleCommit()
	{
		Assert::exception(function () {
			$this->transaction->begin();
			$this->transaction->commit();
			$this->transaction->commit();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testDoubleRollback()
	{
		Assert::exception(function () {
			$this->transaction->begin();
			$this->transaction->rollback();
			$this->transaction->rollback();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testNestedCommit()
	{
		$this->transaction->begin();
		$this->table()->insert(['text' => time()]);
		$this->transaction->begin();
		$this->table()->insert(['text' => time()]);
		$this->transaction->commit();
		$this->transaction->commit();

		$this->validateCount(2);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testNestedCommitAndNestedRollback()
	{
		$this->transaction->begin();
		$this->table()->insert(['text' => 'a']);

		// --
		$this->transaction->begin();
		$this->table()->insert(['text' => 'b']);
		$this->transaction->rollback();
		// --

		$this->transaction->commit();

		$this->validateCount(1);
		Assert::equal('a', $this->table()->fetch()->text);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testNestedCommitAndRollback()
	{
		$this->transaction->begin();
		$this->table()->insert(['text' => 'a']);

		// --
		$this->transaction->begin();
		$this->table()->insert(['text' => 'b']);
		$this->transaction->commit();
		// --

		$this->transaction->rollback();

		$this->validateCount(0);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testTransactional()
	{
		$this->transaction->transaction(function () {
			$this->table()->insert(['text' => time()]);
		});

		$this->validateCount(1);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testTransactionalAlias()
	{
		$this->transaction->t(function () {
			$this->table()->insert(['text' => time()]);
		});

		$this->validateCount(1);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testTransactionalFailed()
	{
		Assert::exception(function () {
			$this->transaction->transaction(function () {
				$this->table()->insert([time() => time()]);
			});
		}, DriverException::class);

		$this->validateCount(0);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testNestedTransactional()
	{
		$this->transaction->transaction(function () {
			$this->table()->insert(['text' => time()]);

			$this->transaction->transaction(function () {
				$this->table()->insert(['text' => time()]);
			});
		});

		$this->validateCount(2);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testNestedTransactionalNestedFailed()
	{
		$this->transaction->transaction(function () {
			$this->table()->insert(['text' => time()]);

			Assert::exception(function () {
				$this->transaction->transaction(function () {
					$this->table()->insert([time() => time()]);
				});
			}, DriverException::class);
		});

		$this->validateCount(1);
	}

}

(new TransactionTest())->run();
