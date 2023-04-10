# Redact Sensitive [![CI](https://github.com/leocavalcante/redact-sensitive/actions/workflows/ci.yml/badge.svg)](https://github.com/leocavalcante/redact-sensitive/actions/workflows/ci.yml)

🙈 A Monolog processor that protects sensitive data from miss logging.

Avoids logging something like `{"api_key":"mysupersecretapikey"}` by masking partially or completely sensitive data:
```text
Readme.INFO: Hello, World! {"api_key":"mysu***************"} []
```

## Install
```shell
composer require leocavalcante/redact-sensitive
```

## Usage

### 1. Prepare your sensitive keys

It is a map of key names and how much of it can be displayed, for example:
```php
$sensitive_keys = [
    'api_key' => 4,
];
```
Shows the first 4 characters of the `api_key`.

#### If you want to display the last chars, you can use negative values like `['api_key' => -4]`, then it will display the last 4 characters.

### 2. Create a Processor using the keys

You can now create a new Processor with the given keys:

```php
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['api_key' => 4];

$processor = new RedactSensitiveProcessor($sensitive_keys);
```

### 3. Set the Processor to a Monolog\Logger

```php
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['api_key' => 4];

$processor = new RedactSensitiveProcessor($sensitive_keys);

$logger = new \Monolog\Logger('Readme');
$logger->pushProcessor($processor);
```

## Examples

```php
use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['api_key' => 4];

$processor = new RedactSensitiveProcessor($sensitive_keys);

$logger = new \Monolog\Logger('Readme', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$logger->info('Hello, World!', ['api_key' => 'mysupersecretapikey']);
```
```text
Readme.INFO: Hello, World! {"api_key":"mysu***************"} []
```

### Completely hidden

You can hide it completely by passing `0` to the key.

```php
use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['you_know_nothing' => 0];

$processor = new RedactSensitiveProcessor($sensitive_keys);

$logger = new \Monolog\Logger('Example', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$logger->info('Completely hidden', ['you_know_nothing' => 'John Snow']);
```
```text
Example.INFO: Completely hidden {"you_know_nothing":"*********"} []
```

### Custom format

Feel free to customize a replacement character `*` and/or provide your own template.

```php
use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['secret' => 2];

$processor = new RedactSensitiveProcessor($sensitive_keys, template: '%s(redacted)');

$logger = new \Monolog\Logger('Example', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$logger->info('Sensitive', ['secret' => 'my_secret_value']);
```
```text
Example.INFO: Sensitive {"secret":"my*************(redacted)"} []
```

Custom template allows to discard the masked characters altogether:
```php
use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['secret' => 2];

$processor = new RedactSensitiveProcessor($sensitive_keys, template: '...');

$logger = new \Monolog\Logger('Example', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$logger->info('Sensitive', ['secret' => 'my_secret_value']);
```
```text
Example.INFO: Sensitive {"secret":"my..."} []
```

### Length limit

Use `lengthLimit` to truncate redacted sensitive information, such as lengthy tokens.

```php
use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['access_token' => 0];

$processor = new RedactSensitiveProcessor($sensitive_keys, lengthLimit: 5);

$logger = new \Monolog\Logger('Example', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$logger->info('Truncated secret', ['access_token' => 'Very long JWT ...']);
```
```text
Example.INFO: Truncated secret {"access_token":"*****"} []
```

### Right to left

And, as said before, you can mask the value from right to left using negative values.

```php
use Monolog\Handler\StreamHandler;
use RedactSensitive\RedactSensitiveProcessor;

$sensitive_keys = ['credit_card' => -4];

$processor = new RedactSensitiveProcessor($sensitive_keys);

$logger = new \Monolog\Logger('Example', [new StreamHandler(STDOUT)]);
$logger->pushProcessor($processor);

$logger->info('You are not storing credit cards, right?', ['credit_card' => '4111111145551142']);
```
```text
Example.INFO: You are not storing credit cards, right? {"credit_card":"************1142"} []
```

### Nested values

It should work with nested objects and arrays as well.

```php
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
```
```text
Example.INFO: Nested {"nested":{"arr":{"value":"abc***","or_obj":{"stdClass":{"secret":"***********one"}}}}} []
```

## Thanks
Feel free to open any issues or PRs.

---
MIT &copy; 2021
