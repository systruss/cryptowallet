# Delegate Ubuntu Server "DUS"
The Delegates Ubuntu server calculation of the redistribution to voters of the delegate's incomes

<p align="center">
    <img src="/banner_infi.png" />
</p>

[![License: MIT](https://badgen.now.sh/badge/license/MIT/green)](https://opensource.org/licenses/MIT)

> Lead Maintainer: [Infinity developers](https://github.com/Plusid)

## What's for?

the DUS is designed to help delegates share their income with voters.
Beforehand, the delegate chooses these redistribution criteria on the mobile APP menu "Delegate Setting"
The wallet calculated according to the delegate's criteria

## What's beneficiary wallet?

In the redistribution criteria there is a beneficiary wallet.
This wallet is different from the delegate wallet:
If the delegate chooses who will redistribute 80% of his income.
The due will calculate that the beneficiary wallet will receive 20% of the delegate's income.

## Why some accounts is exclude from the distribution?

The delegate can choose to exclude voters with a low balance from the redistribution to limit the dilution of income generated by transaction fees
If the chosen delegate excludes the accounts which have a too low balance, the DUS excludes them from the redistribution.the votes count for the delegate but the DUS excludes them from the redistribution.

### Server Prerequisites

- minimum VPS recommended 
1 CPU 1GB 
25GB Disk
1000GB transfer

- ubuntu 20

### Setup

<details><summary>Install</summary>

Install Delegate Ubuntu Server 

1 - install Laravel and create a project
```bash
# composer create-project laravel/laravel <project name>
```

2 - Update the .env file to point it to you Database server.

3 - add the folowings packages :
```bash
# composer require arkecosystem/crypto
# composer require systruss/schedtransactions
```

4 - Run artisan migrate to create the tables in Database.

```bash
# php artisan migrate
```
5 - register wallet

```bash
# php artisan crypto:register
```

Choice your blockchain "infinity" or "hedge"
Enter your network: 

```bash
infinity
```
or
```bash
hedge
```

Entry your delegate phrase (wallet passphrase) as forger
Enter your wallet delegate:

```bash
"this is my secret passphrase"
```

6 - update the ubuntu system crontab

```bash
# php artisan crypto:cron add_cron
```

7 - restart cron :
```bash 
    Open a shell command in a terminal and run :
    sudo systemctl restart cron
```

8 - enable scheduler
```bash
# php artisan crypto:admin enable_sched
```

9 - To monitor your application you can use the followings :
```bash
    a - check scheduler logs : storage/logs/schedule_job.log
    b - php artisan crypto:admin show_logs
```

</details>



## GitHub Development Bounty

-   Get involved with the development and start earning INFI : https://bounty.infinitysoftware.io

## Security

If you discover a security vulnerability within this package, please send an e-mail to security@infinitysoftware.io. All security vulnerabilities will be promptly addressed.

## License

[MIT](LICENSE) © [INFINITY Ecosystem](https://infinitysoftware.io)