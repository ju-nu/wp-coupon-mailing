<?php

use App\Auth;

require __DIR__ . '/../../vendor/autoload.php';

Auth::logout();
header("Location: login.php");
