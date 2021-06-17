<?php declare(strict_types=1);

namespace RedactSensitiveTests;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use RedactSensitive\RedactSensitiveProcessor;

it('plays nice with monolog', function (): void {
    $handler = new TestHandler();
    $processor = new RedactSensitiveProcessor(['test_key' => 4]);

    $logger = new Logger('Test', [$handler], [$processor]);
    $logger->info('Testing', ['test_key' => 'test_value']);

    expect($handler->hasRecordThatPasses(function (array $record): bool {
        return $record['context']['test_key'] === 'test******';
    }, Logger::INFO))->toBeTrue();
});