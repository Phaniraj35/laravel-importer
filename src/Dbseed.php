<?php namespace Ladybird\import;

use Flynsarmy\CsvSeeder\CsvSeeder;

class Dbseed extends CsvSeeder {

	public function __construct($table,$filepath,$chunk)
	{
		$this->table = $table;
        $this->filename = $filepath;
        $this->insert_chunk_size = $chunk;
	}

	public function run()
	{
		// Recommended when importing larger CSVs
		\DB::disableQueryLog();

		// Uncomment the below to wipe the table clean before populating
		//\DB::table($this->table)->truncate();

		parent::run();
	}
}