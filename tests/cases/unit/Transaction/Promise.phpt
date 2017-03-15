<?php

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
	 * @return void
	 */
	public function testPromiseFulfilled()
	{
		$testPromiseFulfilled = NULL;
		$this->transaction->promise()->then(
			function () use (&$fulfilled) {
				$this->table()->insert(['text' => time()]);
				$fulfilled = TRUE;
			}
		);

		Assert::true($fulfilled);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testPromiseCompleted()
	{
		$success = NULL;
		$this->transaction->promise()->then(
			function () {
				$this->table()->insert(['text' => time()]);
			},
			function () use (&$success) {
				$success = TRUE;
			},
			function () use (&$success) {
				$success = FALSE;
			}
		);

		Assert::true($success);
	}

	/**
	 * @test
	 * @return void
	 */
	public function testPromiseRejected()
	{
		$rejected = NULL;
		$this->transaction->promise()->then(
			function () {
				$this->table()->insert([time() => time()]);
			},
			function () use (&$rejected) {
				$rejected = TRUE;
			},
			function () use (&$rejected) {
				$rejected = FALSE;
			}
		);

		Assert::false($rejected);
	}

}

(new PromiseTest())->run();
