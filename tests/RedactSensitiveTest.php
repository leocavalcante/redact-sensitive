<?php declare(strict_types=1);

namespace RedactSensitiveTests;

use RedactSensitive\RedactSensitiveProcessor;

it('redacts records contexts', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = ['context' => ['test' => 'foobar']];
    expect($processor($record))->toBe(['context' => ['test' => 'foo***']]);
});

it('overrides default replacement', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys, '_');

    $record = ['context' => ['test' => 'foobar']];
    expect($processor($record))->toBe(['context' => ['test' => 'foo___']]);
});

it('redacts from right to left', function (): void {
    $sensitive_keys = ['test' => -3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = ['context' => ['test' => 'foobar']];
    expect($processor($record))->toBe(['context' => ['test' => '***bar']]);
});

it('redacts nested arrays', function (): void {
    $sensitive_keys = ['test' => ['nested' => 3]];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = ['context' => ['test' => ['nested' => 'foobar']]];
    expect($processor($record))->toBe(['context' => ['test' => ['nested' => 'foo***']]]);
});

it('redacts inside nested arrays', function (): void {
    $sensitive_keys = ['nested' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => ['nested' => 'foobar']]);
    expect($processor($record)->context)->toBe(['test' => ['nested' => 'foo***']]);
});

it('redacts nested objects', function (): void {
    $nested = new \stdClass();
    $nested->value = 'foobar';
    $nested->nested = ['value' => 'bazqux'];

    $sensitive_keys = ['test' => ['nested' => ['value' => 3, 'nested' => ['value' => -3]]]];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = ['context' => ['test' => ['nested' => $nested]]];

    expect($processor($record))->toBe(['context' => ['test' => ['nested' => $nested]]]);
    expect($nested->value)->toBe('foo***');
    expect($nested->nested['value'])->toBe('***qux');
});

it('redacts inside nested objects', function (): void {
    $nested = new \stdClass();
    $nested->value = 'foobar';
    $nested->nested = ['value' => 'bazqux'];

    $sensitive_keys = ['nested' => ['value' => -3]];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => ['nested' => $nested]]);

    expect($processor($record)->context)->toBe(['test' => ['nested' => $nested]]);
    expect($nested->value)->toBe('***bar');
    expect($nested->nested['value'])->toBe('***qux');
});

it('throws when finds an un-traversable value', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = ['context' => ['test' => fopen(__FILE__, 'rb')]];
    $processor($record);
})->throws(\UnexpectedValueException::class, 'Don\'t know how to traverse value at key test');