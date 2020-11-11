<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use App\Command\CreateItemsCommand;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new CreateItemsCommand());
$application->run();