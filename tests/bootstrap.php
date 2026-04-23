<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

// Ensure APP_ENV=test is honoured from phpunit.xml <server> settings
// bootEnv reads $_SERVER but writes to $_ENV; KernelTestCase reads $_ENV first.
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] ?? 'test';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}

// Bootstrap test database
$console = dirname(__DIR__).'/bin/console';
foreach ([
    ['doctrine:database:create', '--if-not-exists', '--env=test', '-q'],
    ['doctrine:migrations:migrate', '--no-interaction', '--env=test', '-q'],
] as $cmd) {
    $process = new Process(['php', $console, ...$cmd]);
    $process->run();
    if (!$process->isSuccessful()) {
        echo $process->getErrorOutput();
        exit(1);
    }
}
