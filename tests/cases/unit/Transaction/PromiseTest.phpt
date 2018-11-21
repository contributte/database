<?php declare(strict_types = 1);

/**
 * Test: Contributte\Database\Transaction\Promise
 *
 * @testCase
 */

namespace Tests\Cases\Unit\Transaction;

use Tester\Assert;
use Tests\Helpers\BaseTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

final class PromiseTest extends BaseTestCase
{

	/**
	 * @test
	 */
	public function testPromiseFulfilled(): void
	{
		$testPromiseFulfilled = null;
		$this->transaction->promise()->then(
			function () use (&$fulfilled): void {
				$this->table()->insert(['text' => time()]);
				$fulfilled = true;
			}
		);

		Assert::true($fulfilled);
	}

	/**
	 * @test
	 */
	public function testPromiseCompleted(): void
	{
		$success = null;
		$this->transaction->promise()->then(
			function (): void {
				$this->table()->insert(['text' => time()]);
			},
			function () use (&$success): void {
				$success = true;
			},
			function () use (&$success): void {
				$success = false;
			}
		);

		Assert::true($success);
	}

	/**
	 * @test
	 */
	public function testPromiseRejected(): void
	{
		$rejected = null;
		$this->transaction->promise()->then(
			function (): void {
				$this->table()->insert([time() => time()]);
			},
			function () use (&$rejected): void {
				$rejected = true;
			},
			function () use (&$rejected): void {
				$rejected = false;
			}
		);

		Assert::false($rejected);
	}

}

(new PromiseTest())->run();
