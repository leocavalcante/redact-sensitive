<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = [
    'nested' => [
        'arr' => [
            'value' => 3,
            'or_obj' => ['secret' => -3],
        ],
    ]
];

$processor = new RedactSensitiveProcessor($sensitive_keys);

$logger = new \Monolog\Logger('Example', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$nested_obj = new stdClass();
$nested_obj->secret = 'donttellanyone';

$logger->info('Nested', [
    'nested' => [
        'arr' => [
            'value' => 'abcdfg',
            'or_obj' => $nested_obj,
        ],
    ],
]);