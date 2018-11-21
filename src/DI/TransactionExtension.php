<?php declare(strict_types = 1);

namespace Contributte\Database\DI;

use Contributte\Database\Transaction\Transaction;
use Nette\Database\Connection;
use Nette\DI\CompilerExtension;

class TransactionExtension extends CompilerExtension
{

	/**
	 * Decorate services
	 */
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		// If connection exists
		if ($builder->getByType(Connection::class) !== null) {
			$builder->addDefinition($this->prefix('transaction'))
				->setFactory(Transaction::class);
		}
	}

}
