<?php

namespace PHPNomad\Enum\Traits;

use PHPNomad\Singleton\Traits\WithInstance;
use ReflectionClass;
use UnexpectedValueException;

trait Enum
{
    use WithInstance;

    private array $values;

    /**
     * Prevent construction of instances.
     */
    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * Validate the value against the enum values.
     *
     * @param mixed $value
     * @return bool
     */
    public static function isValid($value): bool
    {
        return in_array($value, self::getValues(), true);
    }

    /**
     * Get all enum values.
     *
     * @return array
     */
    public static function getValues(): array
    {
        if(!isset(static::instance()->values)){
            // Using reflection to get the constants of the caller class
            $calledClass = get_called_class();
            $reflection = new ReflectionClass($calledClass);
            static::instance()->values = array_values($reflection->getConstants());
        }

        return static::instance()->values;
    }

    /**
     * Retrieves all possible cases for the enum.
     *
     * @return array
     */
    public static function cases(): array
    {
        return self::getValues();
    }

    /**
     * Attempt to retrieve an enum case by value.
     *
     * @param mixed $value
     * @return mixed|null
     */
    public static function tryFrom($value)
    {
        if (!self::isValid($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Retrieve an enum case by value. Throws an exception if the value is invalid.
     *
     * @param mixed $value
     * @return mixed
     * @throws UnexpectedValueException
     */
    public static function from($value)
    {
        if (!self::isValid($value)) {
            throw new UnexpectedValueException("Value '{$value}' is not part of the enum " . static::class);
        }

        return $value;
    }
}