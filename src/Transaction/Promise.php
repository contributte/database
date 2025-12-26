<?php declare(strict_types = 1);

namespace Contributte\Database\Transaction;

use Throwable;

class Promise
{

	private Transaction $transaction;

	public function __construct(Transaction $transaction)
	{
		$this->transaction = $transaction;
	}

	public function then(callable $onFulfilled, ?callable $onCompleted = null, ?callable $onRejected = null): void
	{
		// Start transaction
		$this->transaction->begin();

		try {
			// Fire onFulfilled!
			$onFulfilled($this->transaction->getConnection());

			// Commit transaction
			$this->transaction->commit();

			if ($onCompleted !== null) {
				// Fire onCompleted!
				$onCompleted();
			}
		} catch (Throwable $e) {
			// Rollback transaction
			$this->transaction->rollback();

			if ($onRejected !== null) {
				// Fire onRejected!
				$onRejected($e);
			}
		}
	}

}
