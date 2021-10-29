<?php

declare(strict_types=1);

namespace console;

/**
 * Migrations run console application.
 * @property-read \core\orm\EloquentOrm $orm ORM instance.
 */
class ApplicationMigration extends Application
{
	/**
	 * @inheritDoc
	 */
	protected function init(): void
	{
		parent::init();

		// Get default DB connection config
		$dbDefaultConfig = $this->orm->getConnection()->getConfig();

		// Initialize old DB connection, used to import old data
		$dbOldConfig = $dbDefaultConfig;
		$dbOldConfig['database'] = 'db_old';
		$this->orm->addConnection($dbOldConfig, 'old');
	}

	/**
	 * @inheritDoc
	 */
	public function run(): void
	{
		echo "Migrations runner...\n";
	}
}