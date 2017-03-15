<?php

/**
 * Test: Contributte\Database\Exception\UnresolvedException
 *
 * @testCase
 */

namespace Tests\Cases\Unit\Transaction;

use Contributte\Database\Exception\UnresolvedTransactionException;
use Tester\Assert;
use Tests\Helpers\BaseTestCase;

require_once __DIR__ . '/../../../bootstrap.php';

final class UnresolvedException extends BaseTestCase
{

	/**
	 * @test
	 * @return void
	 */
	public function testThrows()
	{
		/** @var UnresolvedTransactionException $exception */
		$exception = NULL;
		$this->transaction->onUnresolved[] = function (UnresolvedTransactionException $e) use (&$exception) {
			$exception = $e;
		};

		// Begin transaction
		$this->transaction->begin();
		$this->table()->insert(['text' => time()]);

		// Remove reference
		$this->transaction = NULL;

		Assert::notEqual(NULL, $exception);
		Assert::type(UnresolvedTransactionException::class, $exception);
	}

}

(new UnresolvedException())->run();
