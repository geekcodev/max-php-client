<?php

declare(strict_types=1);

namespace GeekCo\MaxPhpClient\Internal;

use BackedEnum;
use GeekCo\MaxPhpClient\Exception\InvalidResponseException;

final class Json
{
    public static function requiredInt(array $data, string $key): int
    {
        if (!isset($data[$key]) || !\is_int($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be an integer.', $key));
        }

        return $data[$key];
    }

    public static function int(array $data, string $key): ?int
    {
        if (!isset($data[$key])) {
            return null;
        }

        if (!\is_int($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be an integer.', $key));
        }

        return $data[$key];
    }

    /**
     * Int, который API в некоторых объектах отдаёт не как int: числовая строка
     * (int64 в `link`) и целочисленный float приводятся к int, отсутствующее
     * значение — null. Неоднозначные значения (объект, «5.5», «007») отклоняются:
     * это уже не то же самое число.
     */
    public static function tolerantInt(array $data, string $key): ?int
    {
        if (!isset($data[$key])) {
            return null;
        }

        $value = $data[$key];

        if (\is_int($value)) {
            return $value;
        }

        if (\is_float($value) && $value === floor($value) && abs($value) <= (float) PHP_INT_MAX) {
            return (int) $value;
        }

        if (\is_string($value) && ($int = filter_var(trim($value), FILTER_VALIDATE_INT)) !== false) {
            return $int;
        }

        throw new InvalidResponseException(sprintf(
            'Field "%s" must be an integer or a numeric string, got %s.',
            $key,
            self::describe($value),
        ));
    }

    /**
     * Компактное представление значения для сообщения об ошибке: показывает, что
     * именно прислал API (объект, float, строка с мусором). JSON без
     * JSON_UNESCAPED_UNICODE — чтобы обрезка по байтам не разрывала символы.
     */
    private static function describe(mixed $value): string
    {
        $encoded = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if ($encoded === false) {
            return \get_debug_type($value);
        }

        return strlen($encoded) > 64 ? substr($encoded, 0, 61).'...' : $encoded;
    }

    public static function requiredString(array $data, string $key): string
    {
        if (!isset($data[$key]) || !\is_string($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be a string.', $key));
        }

        return $data[$key];
    }

    public static function string(array $data, string $key): ?string
    {
        if (!isset($data[$key])) {
            return null;
        }

        if (!\is_string($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be a string.', $key));
        }

        return $data[$key];
    }

    public static function requiredBool(array $data, string $key): bool
    {
        if (!isset($data[$key]) || !\is_bool($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be a boolean.', $key));
        }

        return $data[$key];
    }

    public static function bool(array $data, string $key): ?bool
    {
        if (!isset($data[$key])) {
            return null;
        }

        if (!\is_bool($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be a boolean.', $key));
        }

        return $data[$key];
    }

    public static function float(array $data, string $key): ?float
    {
        if (!isset($data[$key])) {
            return null;
        }

        if (!\is_int($data[$key]) && !\is_float($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be a number.', $key));
        }

        return (float) $data[$key];
    }

    public static function requiredArray(array $data, string $key): array
    {
        if (!isset($data[$key]) || !\is_array($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be an array.', $key));
        }

        return $data[$key];
    }

    public static function array_(array $data, string $key): ?array
    {
        if (!isset($data[$key])) {
            return null;
        }

        if (!\is_array($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be an array.', $key));
        }

        return $data[$key];
    }

    /**
     * @return list<string>|null
     */
    public static function arrayOfStrings(array $data, string $key): ?array
    {
        $list = self::array_($data, $key);
        if ($list === null) {
            return null;
        }

        foreach ($list as $value) {
            if (!\is_string($value)) {
                throw new InvalidResponseException(sprintf('Field "%s" must be an array of strings.', $key));
            }
        }

        return array_values($list);
    }

    /**
     * @return list<int>|null
     */
    public static function arrayOfInts(array $data, string $key): ?array
    {
        $list = self::array_($data, $key);
        if ($list === null) {
            return null;
        }

        foreach ($list as $value) {
            if (!\is_int($value)) {
                throw new InvalidResponseException(sprintf('Field "%s" must be an array of integers.', $key));
            }
        }

        return array_values($list);
    }

    /**
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     *
     * @return T|null
     */
    public static function enum(string $enumClass, array $data, string $key): ?BackedEnum
    {
        if (!isset($data[$key])) {
            return null;
        }

        if (!\is_string($data[$key])) {
            throw new InvalidResponseException(sprintf('Field "%s" must be a string.', $key));
        }

        $enum = $enumClass::tryFrom($data[$key]);
        if ($enum === null) {
            throw new InvalidResponseException(
                sprintf('Field "%s" has unsupported value "%s".', $key, $data[$key]),
            );
        }

        return $enum;
    }

    /**
     * @template T
     *
     * @param callable(mixed): T $mapper
     *
     * @return list<T>|null
     */
    public static function map(array $data, string $key, callable $mapper): ?array
    {
        $list = self::array_($data, $key);
        if ($list === null) {
            return null;
        }

        return array_values(array_map($mapper, $list));
    }
}
