<?php

declare(strict_types=1);

namespace RedactSensitiveTests;

use RedactSensitive\RedactSensitiveProcessor;

it('redacts records contexts', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => 'foo***']);
});

it('redacts using template', function (): void {
    $sensitive_keys = ['test' => 2];
    $processor = new RedactSensitiveProcessor($sensitive_keys, template: '%s(redacted)');

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => 'fo****(redacted)']);
});

it('redacts discarding masked', function (): void {
    $sensitive_keys = ['test' => 1];
    $processor = new RedactSensitiveProcessor($sensitive_keys, template: '...');

    $record = $this->getRecord(context: ['test' => 'foobar123']);
    expect($processor($record)->context)->toBe(['test' => 'f...']);
});

it('truncates masked characters', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys, lengthLimit: 5);

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => 'foo**']);
});

it('truncates visible characters', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys, lengthLimit: 2);

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => 'fo']);
});

it('overrides default replacement', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys, '_');

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => 'foo___']);
});

it('redacts from right to left', function (): void {
    $sensitive_keys = ['test' => -3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => '***bar']);
});

it('truncates masked from right to left', function (): void {
    $sensitive_keys = ['test' => -3];
    $processor = new RedactSensitiveProcessor($sensitive_keys, lengthLimit: 4);

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => '*bar']);
});

it('truncates visible from right to left', function (): void {
    $sensitive_keys = ['test' => -3];
    $processor = new RedactSensitiveProcessor($sensitive_keys, lengthLimit: 2);

    $record = $this->getRecord(context: ['test' => 'foobar']);
    expect($processor($record)->context)->toBe(['test' => 'ar']);
});

it('redacts nested arrays', function (): void {
    $sensitive_keys = ['test' => ['nested' => 3]];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => ['nested' => 'foobar']]);
    expect($processor($record)->context)->toBe(['test' => ['nested' => 'foo***']]);
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

    $record = $this->getRecord(context: ['test' => ['nested' => $nested]]);

    expect($processor($record)->context)->toBe(['test' => ['nested' => $nested]]);
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

it('preserves empty values', function (): void {
    $sensitive_keys = ['test' => 3, 'optionalKey' => 10];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => 'foobar', 'optionalKey' => '']);
    expect($processor($record)->context)->toBe(['test' => 'foo***', 'optionalKey' => '']);
});

it('throws when finds an un-traversable value', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => fopen(__FILE__, 'rb')]);
    $processor($record);
})->throws(\UnexpectedValueException::class, 'Don\'t know how to traverse value at key test');

it('ignore when null value', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: ['test' => 'foobar', 'optionalKey' => null]);
    expect($processor($record)->context)->toBe(['test' => 'foo***', 'optionalKey' => null]);
});

it('redacts nested values when key is integer', function (): void {
    $sensitive_keys = ['test' => 3];
    $processor = new RedactSensitiveProcessor($sensitive_keys);

    $record = $this->getRecord(context: [0 => ['good' => 'value'], 1 => ['test' => 'foobar']]);
    expect($processor($record)->context)->toBe([0 => ['good' => 'value'], 1 => ['test' => 'foo***']]);
});
