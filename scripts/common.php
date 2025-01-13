<?php

// scripts/common.php
// Optionally load config, etc.

use App\Config;
use App\Database;

require __DIR__ . '/../vendor/autoload.php';

Config::init();
$pdo = Database::connect();

// Additional shared logic...
