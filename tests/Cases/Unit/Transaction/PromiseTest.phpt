<?php declare(strict_types = 1);

/**
 * Test: Contributte\Database\Transaction\Promise
 *
 * @testCase
 */

namespace Tests\Cases\Unit\Transaction;

use stdClass;
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
		$context = new stdClass();
		$context->fulfilled = false;

		$this->transaction->promise()->then(
			function () use ($context): void {
				$this->table()->insert(['text' => time()]);
				$context->fulfilled = true;
			}
		);

		Assert::true($context->fulfilled);
	}

	/**
	 * @test
	 */
	public function testPromiseCompleted(): void
	{
		$context = new stdClass();
		$context->success = null;

		$this->transaction->promise()->then(
			function (): void {
				$this->table()->insert(['text' => time()]);
			},
			function () use ($context): void {
				$context->success = true;
			},
			function () use ($context): void {
				$context->success = false;
			}
		);

		Assert::true($context->success);
	}

	/**
	 * @test
	 */
	public function testPromiseRejected(): void
	{
		$context = new stdClass();
		$context->rejected = null;

		$this->transaction->promise()->then(
			function (): void {
				$this->table()->insert([time() => time()]);
			},
			function () use ($context): void {
				$context->rejected = true;
			},
			function () use ($context): void {
				$context->rejected = false;
			}
		);

		Assert::false($context->rejected);
	}

}

(new PromiseTest())->run();
