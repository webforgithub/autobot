# autobot
It's auto trading bot for binance exchange.

Setup steps:
- Download source code.
- Go to root directory of the source code
- Run composer command -> composer install
- Once composer installation done, setup database name and sql connection in .env files.
- Once database is setup, run migration command -> php artisan migrate (You can also user the SQL script to import database)
- You can find SQL script in  database/sql-dump.sql

Once above steps are done then install trader php extension.
- Steps for Ubuntu OS

sudo apt-get update
sudo apt-get install php-pear php7-dev 
pear install trader

- Steps for Windows OS
https://stackoverflow.com/questions/48685310/how-to-install-php-trader-php-ext-in-windows-10-with-xampp
http://php.net/manual/en/install.pecl.windows.php

NOTE: Project works with PHP 7.0 and higher version.

- How to run MACD based buy/sell ?
Run following commands
-> php artisan autobot:testmacd_strategies --no-ansi

- Update allocated wallet balance.
Run following commands
-> php artisan autobot:checkbalance --no-ansi