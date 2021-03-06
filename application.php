#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use App\Command\CheckCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env.local');

$application = new Application('Proxy checker', 'v1.0.0');
$application->add(new CheckCommand());
$application->run();
