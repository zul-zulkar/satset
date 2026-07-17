<?php
/**
 * Shared test assertion helper.
 * Tidak butuh library eksternal — cukup PHP CLI.
 *
 * Suite test pakai: require_once __DIR__ . '/../_lib/T.php';
 */
class T {
    public static int $pass = 0;
    public static int $fail = 0;
    public static int $skip = 0;

    public static function header(string $title): void {
        echo "\n\e[36m▶ {$title}\e[0m\n";
    }

    public static function ok(string $name, bool $result): void {
        if ($result) {
            echo "\e[32m  ✓ {$name}\e[0m\n";
            self::$pass++;
        } else {
            echo "\e[31m  ✗ {$name}\e[0m\n";
            self::$fail++;
        }
    }

    public static function eq(string $name, mixed $expected, mixed $actual): void {
        $eq = $expected === $actual;
        self::ok("{$name} [expect=" . json_encode($expected) . " got=" . json_encode($actual) . "]", $eq);
    }

    public static function skip(string $name, string $reason = ''): void {
        echo "\e[33m  ↷ {$name}" . ($reason ? " ({$reason})" : '') . "\e[0m\n";
        self::$skip++;
    }

    public static function reset(): void {
        self::$pass = 0;
        self::$fail = 0;
        self::$skip = 0;
    }

    public static function summary(): array {
        return ['pass' => self::$pass, 'fail' => self::$fail, 'skip' => self::$skip];
    }
}
