<?php

namespace Contributte\Database\Transaction;

use Exception;

class Promise
{

	/** @var Transaction */
	private $transaction;

	/**
	 * @param Transaction $transaction
	 */
	public function __construct(Transaction $transaction)
	{
		$this->transaction = $transaction;
	}

	/**
	 * @param callable $onFulfilled
	 * @param callable|NULL $onCompleted
	 * @param callable|NULL $onRejected
	 * @return void
	 */
	public function then(callable $onFulfilled, callable $onCompleted = NULL, callable $onRejected = NULL)
	{
		// Start transaction
		$this->transaction->begin();

		try {
			// Fire onFulfilled!
			$onFulfilled($this->transaction->getConnection());

			// Commit transaction
			$this->transaction->commit();

			if ($onCompleted) {
				// Fire onCompleted!
				$onCompleted();
			}
		} catch (Exception $e) {
			// Rollback transaction
			$this->transaction->rollback();

			if ($onRejected) {
				// Fire onRejected!
				$onRejected($e);
			}
		}
	}

}
