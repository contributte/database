<?php declare(strict_types = 1);

/**
 * Test: Contributte\Database\Exception\UnresolvedException
 *
 * @testCase
 */

namespace Tests\Cases\Unit\Transaction;

use Contributte\Database\Exception\UnresolvedTransactionException;
use stdClass;
use Tester\Assert;
use Tests\Helpers\BaseTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

final class UnresolvedExceptionTest extends BaseTestCase
{

	/**
	 * @test
	 */
	public function testThrows(): void
	{
		$context = new stdClass();
		$context->exception = null;

		$this->transaction->onUnresolved[] = function (UnresolvedTransactionException $e) use ($context): void {
			$context->exception = $e;
		};

		// Begin transaction
		$this->transaction->begin();
		$this->table()->insert(['text' => time()]);

		// Remove reference
		$this->transaction = null;

		Assert::notEqual(null, $context->exception);
		Assert::type(UnresolvedTransactionException::class, $context->exception);
	}

}

(new UnresolvedExceptionTest())->run();
