<?php

namespace Systruss\SchedTransactions\Services;

use Illuminate\Console\Command;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

use Systruss\SchedTransactions\Models\DelegateDb;
use Systruss\SchedTransactions\Services\Delegate;


const failed = 0;
const succeed = 1;

class Delegates
{
	public $delegates;


	public function initFromDb()
	{
		//get the registered delegates address,network and passphrase 
		$delegates = [];

		//check of delegates tables exist
		if (!Schema::hasTable('delegate_dbs')) {
			echo "\n table delegates does not exist, did you run php artisan migrate ? \n";
			return failed;
		}

		//get number of delegate registered
		$nb_delegates = DelegateDb::count();

		if (!$nb_delegates) {
			//no delegate
			echo "\n there is no delegate defined, did you run php artisan crypto:register ? \n";
			return failed;
		}

		//Build a list a delegates
		$delegates_rows = DB::table('delagate_dbs')->get();
		foreach ($delegates_rows as $delagate_row) {
			$delegate = new Delegate();
			//init delegate
			$delegate->init($delegate_row);
			$this->delegates[] = $delegate;
		}
		
		return succeed;
	}

}