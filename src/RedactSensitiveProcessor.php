<?php declare(strict_types=1);

namespace RedactSensitive;

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

    /**
     * Creates a new RedactSensitiveProcessor instance.
     *
     * @param array $sensitiveKeys Keys that should trigger the redaction.
     * @param string $replacement The replacement character.
     */
    public function __construct(array $sensitiveKeys, string $replacement = self::DEFAULT_REPLACEMENT)
    {
        $this->sensitiveKeys = $sensitiveKeys;
        $this->replacement = $replacement;
    }

    public function __invoke(array $record): array
    {
        $record['context'] = $this->traverseArr($record['context'], $this->sensitiveKeys);
        return $record;
    }

    private function redact(?string $value, int $length = 0): string
    {
        if (is_null($value)) {
            return str_repeat($this->replacement, 4);
        }

        $hidden_length = strlen($value) - abs($length);
        $hidden = str_repeat($this->replacement, $hidden_length);

        return substr_replace($value, $hidden, max(0, $length), $hidden_length);
    }

    /**
     * @param array|object|null $value
     * @param array|int $keys
     * @return array|object|string
     */
    private function traverse(string $key, $value, $keys)
    {
        if (is_null($value)) {
            return $this->redact($value);
        }

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
            if (array_key_exists($key, $keys)) {
                if (is_null($value)) {
                    $arr[$key] = $this->redact($value);
                    continue;
                }

                if (is_scalar($value)) {
                    $length = is_int($keys[$key]) ? $keys[$key] : 0;

                    $arr[$key] = $this->redact((string) $value, $length);
                    continue;
                }

                $arr[$key] = $this->traverse($key, $value, $keys[$key]);
            }
        }

        return $arr;
    }

    private function traverseObj(object $obj, array $keys): object
    {
        foreach (get_object_vars($obj) as $key => $value) {
            if (array_key_exists($key, $keys)) {
                if (is_null($value)) {
                    $obj->{$key} = $this->redact($value);
                    continue;
                }

                if (is_scalar($value)) {
                    $length = is_int($keys[$key]) ? $keys[$key] : 0;

                    $obj->{$key} = $this->redact((string) $value, $length);
                    continue;
                }

                $obj->{$key} = $this->traverse($key, $value, $keys[$key]);
            }
        }

        return $obj;
    }
}