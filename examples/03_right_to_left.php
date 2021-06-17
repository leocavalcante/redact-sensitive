<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['credit_card' => -4];

$processor = new RedactSensitiveProcessor($sensitive_keys);

$logger = new \Monolog\Logger('Example', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$logger->info('You are not storing credit cards, right?', ['credit_card' => '4111111145551142']);