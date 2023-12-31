<?php declare(strict_types=1);
/**
 * Copyright (c) 2023 · Kerem Güneş
 * Apache License 2.0 · https://github.com/okerem/uuid
 */
namespace Uuid;

/**
 * DateTime UUID class, generates date/time prefixed values.
 *
 * @package Uuid
 * @class   Uuid\DateTimeUuid
 * @author  Kerem Güneş
 */
class DateTimeUuid extends Uuid
{
    /** For a valid parsing process. */
    public readonly int|string|null $threshold;

    /**
     * Constructor.
     *
     * @param  Uuid\DateTimeUuid|null $value
     * @param  bool                   $strict
     * @param  string|null            $threshold
     * @throws Uuid\UuidError         If strict & an invalid date/time value given.
     * @override
     */
    public function __construct(string|DateTimeUuid $value = null, bool $strict = true, int|string $threshold = null)
    {
        // Check if value given & strict.
        if (func_num_args() && $strict && !self::validate((string) $value, $strict, $threshold)) {
            throw UuidError::forInvalidDateTimeValue($value);
        }

        $this->threshold = $threshold;

        // Create if none given.
        $value ??= self::generate();

        parent::__construct($value, $strict);
    }

    /**
     * Get date.
     *
     * @param  string|null $separator
     * @return string|null
     */
    public function getDate(string $separator = null): string|null
    {
        @[$date, ] = self::parseDateTime($this->value, $this->threshold);

        // Period separator.
        if ($date !== null && $separator) {
            $date = vsprintf(
                "%s%s{$separator}%s{$separator}%s",
                str_split($date, 2)
            );
        }

        return $date;
    }

    /**
     * Get time.
     *
     * @param  string|null $separator
     * @return string|null
     */
    public function getTime(string $separator = null): string|null
    {
        @[, $time] = self::parseDateTime($this->value, $this->threshold);

        // Period separator.
        if ($time !== null && $separator) {
            $time = vsprintf(
                "%s{$separator}%s{$separator}%s",
                str_split($time, 2)
            );
        }

        return $time;
    }

    /**
     * Get date/time.
     *
     * @param  string $zone
     * @return DateTime|null
     * @throws Uuid\UuidError
     */
    public function getDateTime(string $zone = 'UTC'): \DateTime|null
    {
        @[$date, $time] = self::parseDateTime($this->value, $this->threshold);

        if ($date !== null && $time !== null) {
            try {
                return new \DateTime($date . $time, new \DateTimeZone($zone));
            } catch (\Throwable $e) {
                throw new UuidError($e->getMessage(), $e->getCode(), $e);
            }
        }

        return null;
    }

    /**
     * Check this Uuid value if valid.
     *
     * @param  bool            $strict
     * @param  int|string|null $threshold
     * @return bool
     * @override
     */
    public function isValid(bool $strict = true, int|string $threshold = null): bool
    {
        return self::validate(
            $this->value, $strict,
            $threshold ?? $this->threshold
        );
    }

    /**
     * Generate a date/time prefixed UUID value.
     *
     * @return string
     * @override
     */
    public static function generate(): string
    {
        $date = self::datetime();

        // Q for 64-bit ulong.
        $bins = pack('Q', $date);

        // Drop NUL-padding.
        $bins = substr($bins, 0, -2);

        // Reverse & add random bytes.
        $bins = strrev($bins) . random_bytes(10);

        // Add version/variant.
        $bins = parent::modify($bins);

        return parent::format(bin2hex($bins));
    }

    /**
     * Check given UUID value if valid.
     *
     * @param  string          $uuid
     * @param  bool            $strict
     * @param  int|string|null $threshold
     * @return bool
     * @override
     */
    public static function validate(string $uuid, bool $strict = true, int|string $threshold = null): bool
    {
        if (!parent::validate($uuid, $strict)) {
            return false;
        }
        if (!self::parseDateTime($uuid, $threshold)) {
            return false;
        }

        return true;
    }

    /**
     * Parse date/time from given UUID value.
     *
     * @param  string          $uuid
     * @param  int|string|null $threshold
     * @return array|null
     */
    public static function parseDateTime(string $uuid, int|string $threshold = null): array|null
    {
        $ret = null;

        // Extract usable part from value.
        if (ctype_xdigit($sub = substr(strtr($uuid, ['-' => '']), 0, 12))) {
            $dec = '' . hexdec($sub);
            $tmp = str_split($dec, 2);
            $ret = [join(array_slice($tmp, 0, 4)), join(array_slice($tmp, 4))];
        }

        // Validate.
        if ($ret !== null) {
            if ($threshold && $dec < $threshold) {
                return null;
            }
            if ($dec > self::datetime()) {
                return null;
            }
        }

        return $ret;
    }

    /**
     * Get current UTC date/time.
     */
    private static function datetime(): string
    {
        return gmdate('YmdHis');
    }
}
