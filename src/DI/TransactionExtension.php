<?php

namespace Contributte\Database\DI;

use Contributte\Database\Transaction\Transaction;
use Nette\Database\Connection;
use Nette\DI\CompilerExtension;

class TransactionExtension extends CompilerExtension
{

	/**
	 * Decorate services
	 *
	 * @return void
	 */
	public function beforeCompile()
	{
		$builder = $this->getContainerBuilder();

		// If connection exists
		if ($builder->getByType(Connection::class)) {
			$builder->addDefinition($this->prefix('transaction'))
				->setClass(Transaction::class);
		};
	}

}
