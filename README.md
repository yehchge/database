# yehchge\database

MySQL Database C.R.U.D

* [Installation](#installation)
* [Basic Usage](#basic-usage)

## Installation

``` bash
composer require "yehchge/database"
```

## Basic Usage

MySQL Database C.R.U.D

``` php
<?php declare(strict_types=1);

include "vendor/autoload.php";

use yehchge\database\Database;

$db = new Database();

```

## Test

```bash
cp phpunit.example.xml phpunit.xml
sudo apt install php-sqlite3
composer test
```