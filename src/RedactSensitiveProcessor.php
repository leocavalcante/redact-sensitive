<?php

declare(strict_types=1);

namespace RedactSensitive;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use UnexpectedValueException;

/**
 * The processor to be added to your Monolog instance.
 * @package RedactSensitive
 */
class RedactSensitiveProcessor implements ProcessorInterface
{
    /**
     * @var string The default replacement character.
     */
    public const DEFAULT_REPLACEMENT = '*';

    private array $sensitiveKeys;
    private string $replacement;
    private string $template;
    private ?int $lengthLimit;

    /**
     * Creates a new RedactSensitiveProcessor instance.
     *
     * @param array $sensitiveKeys Keys that should trigger the redaction.
     * @param string $replacement The replacement character.
     * @param string $template Template for replacement characters.
     * @param int|null $lengthLimit Max length after redaction.
     */
    public function __construct(array $sensitiveKeys, string $replacement = self::DEFAULT_REPLACEMENT, string $template = '%s', ?int $lengthLimit = null)
    {
        $this->sensitiveKeys = $sensitiveKeys;
        $this->replacement = $replacement;
        $this->template = $template;
        $this->lengthLimit = $lengthLimit;
    }


    public function __invoke(LogRecord $record): LogRecord
    {
        $redactedContext = $this->traverseArr($record->context, $this->sensitiveKeys);
        return $record->with(context: $redactedContext);
    }

    private function redact(string $value, int $length): string
    {
        $valueLength = strlen($value);

        if ($valueLength === 0) {
            return $value;
        }

        $hiddenLength = $valueLength - abs($length);
        $hidden = str_repeat($this->replacement, $hiddenLength);
        $placeholder = sprintf($this->template, $hidden);

        $result = substr_replace($value, $placeholder, max(0, $length), $hiddenLength);

        return $length > 0
            ? substr($result, 0, $this->lengthLimit)
            : substr($result, -$this->lengthLimit);
    }

    /**
     * @param array|object $value
     * @param array|int $keys
     * @return array|object
     */
    private function traverse(int|string $key, $value, $keys)
    {
        if (is_array($value)) {
            return $this->traverseArr($value, $keys);
        }

        if (is_object($value)) {
            return $this->traverseObj($value, $keys);
        }

        throw new UnexpectedValueException("Don't know how to traverse value at key $key");
    }

    private function traverseArr(array $arr, array $keys): array
    {
        foreach ($arr as $key => $value) {
            if (is_scalar($value)) {
                if (array_key_exists($key, $keys)) {
                    $arr[$key] = $this->redact((string) $value, $keys[$key]);
                }
                continue;
            } else {
                if (array_key_exists($key, $keys)) {
                    $arr[$key] = $this->traverse($key, $value, $keys[$key]);
                } elseif (null !== $value) {
                    $arr[$key] = $this->traverse($key, $value, $keys);
                }
            }
        }

        return $arr;
    }

    private function traverseObj(object $obj, array $keys): object
    {
        foreach (get_object_vars($obj) as $key => $value) {
            if (is_scalar($value)) {
                if (array_key_exists($key, $keys)) {
                    $obj->{$key} = $this->redact((string) $value, $keys[$key]);
                }
                continue;
            } else {
                if (array_key_exists($key, $keys)) {
                    $obj->{$key} = $this->traverse($key, $value, $keys[$key]);
                } elseif (null !== $value) {
                    $obj->{$key} = $this->traverse($key, $value, $keys);
                }
            }
        }

        return $obj;
    }
}
