<?php

namespace Systruss\SchedTransactions\Services;

use Illuminate\Console\Command;

use ArkEcosystem\Crypto\Configuration\Network;
use ArkEcosystem\Crypto\Identities\Address;
use Systruss\SchedTransactions\Services\Networks\MainnetExt;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use ArkEcosystem\Crypto\Transactions\Builder\TransferBuilder;
use ArkEcosystem\Crypto\Transactions\Builder\MultiPaymentBuilder;
use Systruss\SchedTransactions\Models\DelegateDb;
use Systruss\SchedTransactions\Services\Server;


const failed = 0;
const succeed = 1;
const api_delegates_edge_url ="https://api.hedge.infinitysolutions.io/api/delegates";
const api_delegates_infi_url ="https://api.infinitysolutions.io/api/delegates";
const MinDelegateBalance = 100000;
const MinDelegateRank = 1;
const MaxDelegateRank = 25;

class Delegate
{
	public $fee;
	public $nonce;
	public $balance;
	public $wallet_valid;
	public $delegateAddress;
	public $address;
	public $amount;
	public $delegatePassphrase;
	public $network;
	public $voters;
	public $peer_ip;
	public $peer_port;
	public $peers;
	public $sched_active;
	public $transactions;
	public $api_delegates_url;
	public $publicKey;


	public function register($passphrase,$network)
	{
		//get the registered sender address,network and passphrase 
			
		$this->network = $network;
		$this->passphrase = $passphrase;
		$main_net = MainnetExt::new();
		$this->address = Address::fromPassphrase($this->passphrase,$main_net);
		$valid = false;

		
		$valid = $this->checkDelegateValidity();
		if ($valid) 
		{
			// insert delegate into DB table
			$main_net = new MainnetExt;
			$address = Address::fromPassphrase($passphrase,$main_net);
	
			//check if senders table exist
			if (!Schema::hasTable('delegate_dbs')) {
				echo "\n table delegate_dbs does not exist, run php artisan migrate \n";
				return;
			}
			//check if there is a delegate entry in delegate table
			$delegate = DelegateDb::all();
			if (!$delegate->isEmpty()) {
				//delegate exist
				echo "\n There is already a delegate registered! \n";
				return;
			} else {
				//create delegate
				try {
					$delegate = DelegateDb::create([
						'address' => $this->address,
						'passphrase' => $passphrase,
						'network' => $network,
						'sched_active' => false,
					]);
					$registered = succeed;
					echo "\n Delegate registered successfully \n";
				} catch (QueryException $e) {
					echo "\n error : \n";
					$registered = failed; 
					var_dump($e->errorInfo);
					return false;
				}
			}
			return true;
		} else {
				echo "\n delegate not valid \n";
				return false;
		}
	}


	public function initFromDb()
	{
		//get the registered sender address,network and passphrase 
		$response = [];
		if (!Schema::hasTable('delegate_dbs')) {
			echo "\n table delegate does not exist, did you run php artisan migrate ? \n";
			return;
		}
		$delegate = DelegateDb::first();
		if ($delegate) {
			//sender exist
			echo "\n delegate exist \n";
			$this->network = $delegate->network;
			$this->passphrase = $delegate->passphrase;			
			$this->address = $delegate->address;
			$this->sched_active = $delegate->sched_active;
		} else {
			//no delegate
			echo "\n there is no delegate defined, did you run php artisan crypto:register ? \n";
			return failed;

		}
		return true;
	}


	public function getPeers($network)
	{
		//return the list of peer corresponding to the network
		$main_net = MainnetExt::new();
		$api_url = $main_net->peer($network) . "/test";

		while ( 1 == 1)
		{
			$client = new Client();
			try {
				$res = $client->get($api_url);

				$data =  json_decode($res->getBody()->getContents());  
			
				// total number of peers
				$totalCount = $data->meta->totalCount;
				$peers = array('data' => $data->data, 'count' => $totalCount);

				return $peers;
				break;
			} 
			catch (RequestException $e) {
				$response = $e->getResponse();
				$responseBodyAsString = $response->getBody()->getContents();
				$statusCode = $responseBodyAsString->statusCode;
				$error = $responseBodyAsString->error;
				$message = $responseBodyAsString->message;
				echo "\n -------------------------------- \n";
				var_dump($responseBodyAsString);
				if ($statusCode == "429") {
					echo "\n too meny requests retrying  in 5 sec	\n";
					sleep (5);
				}
				echo "\n -------------------------------- \n";	
			}
		}
	}

	public function checkDelegateValidity()
	{
		// check if walet valid
		//
		// go through the peers and check the wallet
		//

		$valid = false;
		$isDelegate = 0;
		$isResigned = 1;
		$nonce = 0;
		$balance = 0;

		// 
		// Check if peers are set
		// 
		$peers = $this->getPeers($this->network);
		if (!$peers) {
			echo "\n checkDelegateValidity : no peers found \n";
			return failed;
		}

		$peer_list = $peers['data'];
		$count = $peers['count'];
		
		// Generate wallet address from passphrase
		$main_net = MainnetExt::new();
		$wallet_address = Address::fromPassphrase($this->passphrase,$main_net);

		foreach ($peer_list as $peer) 
		{
			//build api url
			$ip_add = $peer->ip;
			$port = $peer->ports->{"@arkecosystem/core-wallet-api"};
			$api_url = "http://$ip_add:$port/api".'/wallets/'.$wallet_address;
							
			//get isDelegate, isResigned, nonce and balance
			try {
				$client = new Client();
				$res = $client->get($api_url);
				if ($data = $res->getBody()->getContents()) 
				{
					$data = json_decode($data);                
					$isDelegate = $data->data->isDelegate; 
					$isResigned = $data->data->isResigned; 
					$nonce = $data->data->nonce + 1; 
					$balance = $data->data->balance;
					if ($isDelegate == 1) {
						if ($isResigned == 0) {
							$valid = true;
							// echo "\n isDelegate = $isDelegate   -- isResigned = $isResigned \n";
							break;
						}
					} 					
				}
			} catch (ClientException $e) {
				if ($e->hasResponse()) 
				{
					$status_code =  json_decode($e->getResponse()->getBody())->statusCode;
					if ($status_code == "404") 
					{
						$valid = false;
						break;
					}
    			}
			}
		}
		
		$this->valid = $valid;
		$this->peer_ip = $ip_add;
		$this->peer_port = $port;
 		$this->nonce = $nonce;
		$this->balance = (int)$balance;
		echo "\n checkDelegateValidity -- delegate balance : $this->balance \n";
		echo "api url : $api_url \n"; 
		
		return $valid;
	}

	public function checkDelegateEligibility() 
	{
		$found = false;
		$api_delegates_url = api_delegates_edge_url;

		// check if delegate balance is grater than the minimum required
		echo "\n delegate balance : $this->balance \n";
		if ($this->balance < MinDelegateBalance) {
			echo "\n insufficient balance \n";
			return false;
		}
		// get list of delegate
		$delegate_network = $this->network;


		if ($delegate_network == "infi") {
			echo "\n delegate network : $delegate_network \n";
			$api_delegates_url = api_delegates_infi_url;
		}

		$client = new Client();
		$res = $client->get($api_delegates_url);
		if ($data = $res->getBody()->getContents()) 
		{
			$data = json_decode($data);
			$totalDelegates = $data->meta->totalCount;
			if ($totalDelegates > 0) {
				$listDelegates = $data->data;
				foreach ($listDelegates as $delegate_elem) {
					if ($delegate_elem->address == $this->address) {
						$this->rank = (int)$delegate_elem->rank;
						$this->publicKey = $delegate_elem->publicKey;
						$found = true;
						break;
					}
				}
				if ($found) {
					echo "\n delegate rank : $this->rank \n";					
					if ($this->rank >= MinDelegateRank && $this->rank <= MaxDelegateRank){
						return true;
					}else{
						return false;
					}
				} else {
					echo "\n delegate not found !!! \n";
					return false;
				}
			} else {
				echo "\n  number of delegate 0 !!! \n";
				return false;
			} 			
		} else {
				echo "\n no data returned from the api delagate url !!! \n";
				return false;
			}
	}
}