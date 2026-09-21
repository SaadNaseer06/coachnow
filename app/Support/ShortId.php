<?php

namespace App\Support;

/**
 * Short, shareable opaque IDs (e.g. "k3m9x") for URLs that shouldn't expose raw integers.
 * Reversible via modular multiply (32-bit); codes stay ~5–7 chars for typical IDs.
 */
class ShortId
{
    private const ALPHABET = '23456789abcdefghijkmnpqrstuvwxyz';

    /** Odd multiplier; inverse mod 2^32 for unscramble. */
    private const MUL = 2654435761;

    private const MUL_INV = 244002641;

    public static function encode(int $id, string $namespace = 'app'): string
    {
        if ($id < 1) {
            return '';
        }

        $scrambled = self::scramble($id, $namespace);
        $alphabet = self::ALPHABET;
        $base = strlen($alphabet);
        $out = '';

        do {
            $out = $alphabet[$scrambled % $base].$out;
            $scrambled = intdiv($scrambled, $base);
        } while ($scrambled > 0);

        return $out;
    }

    public static function decode(string $code, string $namespace = 'app'): ?int
    {
        $code = strtolower(trim($code));
        if ($code === '' || ! preg_match('/^[a-z0-9]+$/', $code)) {
            return null;
        }

        $alphabet = self::ALPHABET;
        $base = strlen($alphabet);
        $n = 0;

        foreach (str_split($code) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                return null;
            }
            $n = ($n * $base) + $pos;
        }

        $id = self::unscramble($n, $namespace);

        return $id > 0 && $id < 2_000_000_000 ? $id : null;
    }

    private static function namespaceXor(string $namespace): int
    {
        return abs(crc32(config('app.key').'|sid|'.$namespace)) & 0x7FFFFFFF;
    }

    private static function scramble(int $id, string $namespace): int
    {
        $x = (($id ^ self::namespaceXor($namespace)) * self::MUL) & 0xFFFFFFFF;

        return $x;
    }

    private static function unscramble(int $n, string $namespace): int
    {
        $id = (($n * self::MUL_INV) & 0xFFFFFFFF) ^ self::namespaceXor($namespace);

        return $id & 0x7FFFFFFF;
    }
}
