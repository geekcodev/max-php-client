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
