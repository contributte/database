<?php declare(strict_types = 1);

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
	 */
	public function testCommit(): void
	{
		$this->validateCount(0);

		$this->transaction->begin();
		$this->table()->insert(['text' => time()]);
		$this->transaction->commit();

		$this->validateCount(1);
	}

	/**
	 * @test
	 */
	public function testRollback(): void
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
	 */
	public function testCommitWithoutBegin(): void
	{
		Assert::exception(function (): void {
			$this->transaction->commit();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 */
	public function testRollbackWithoutBegin(): void
	{
		Assert::exception(function (): void {
			$this->transaction->rollback();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 */
	public function testDoubleCommit(): void
	{
		Assert::exception(function (): void {
			$this->transaction->begin();
			$this->transaction->commit();
			$this->transaction->commit();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 */
	public function testDoubleRollback(): void
	{
		Assert::exception(function (): void {
			$this->transaction->begin();
			$this->transaction->rollback();
			$this->transaction->rollback();
		}, InvalidTransactionException::class);
	}

	/**
	 * @test
	 */
	public function testNestedCommit(): void
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
	 */
	public function testNestedCommitAndNestedRollback(): void
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
	 */
	public function testNestedCommitAndRollback(): void
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
	 */
	public function testTransactional(): void
	{
		$this->transaction->transaction(function (): void {
			$this->table()->insert(['text' => time()]);
		});

		$this->validateCount(1);
	}

	/**
	 * @test
	 */
	public function testTransactionalAlias(): void
	{
		$this->transaction->t(function (): void {
			$this->table()->insert(['text' => time()]);
		});

		$this->validateCount(1);
	}

	/**
	 * @test
	 */
	public function testTransactionalFailed(): void
	{
		Assert::exception(function (): void {
			$this->transaction->transaction(function (): void {
				$this->table()->insert([time() => time()]);
			});
		}, DriverException::class);

		$this->validateCount(0);
	}

	/**
	 * @test
	 */
	public function testNestedTransactional(): void
	{
		$this->transaction->transaction(function (): void {
			$this->table()->insert(['text' => time()]);

			$this->transaction->transaction(function (): void {
				$this->table()->insert(['text' => time()]);
			});
		});

		$this->validateCount(2);
	}

	/**
	 * @test
	 */
	public function testNestedTransactionalNestedFailed(): void
	{
		$this->transaction->transaction(function (): void {
			$this->table()->insert(['text' => time()]);

			Assert::exception(function (): void {
				$this->transaction->transaction(function (): void {
					$this->table()->insert([time() => time()]);
				});
			}, DriverException::class);
		});

		$this->validateCount(1);
	}

}

(new TransactionTest())->run();
