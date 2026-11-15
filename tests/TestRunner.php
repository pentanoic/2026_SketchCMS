<?php

class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function assertTrue(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo "  [PASS] {$message}\n";
        } else {
            $this->failed++;
            $this->failures[] = $message;
            echo "  [FAIL] {$message}\n";
        }
    }

    public function assertEquals(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected === $actual) {
            $this->passed++;
            echo "  [PASS] {$message}\n";
        } else {
            $this->failed++;
            $expStr = var_export($expected, true);
            $actStr = var_export($actual, true);
            $this->failures[] = "{$message} (expected {$expStr}, got {$actStr})";
            echo "  [FAIL] {$message} (expected {$expStr}, got {$actStr})\n";
        }
    }

    public function assertIsArray(mixed $value, string $message): void
    {
        $this->assertTrue(is_array($value), $message);
    }

    public function summary(): int
    {
        echo "\n========================================\n";
        echo "Total: " . ($this->passed + $this->failed) . " | Passed: {$this->passed} | Failed: {$this->failed}\n";
        if (!empty($this->failures)) {
            echo "Failures:\n";
            foreach ($this->failures as $f) {
                echo " - {$f}\n";
            }
            return 1;
        }
        return 0;
    }
}
