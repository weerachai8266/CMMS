<?php
/**
 * Lightweight Test Runner Framework
 * ระบบแจ้งซ่อมเครื่องจักร - Test Suite
 */

class TestRunner
{
    private array $results = [];
    private int $passed = 0;
    private int $failed = 0;
    private int $skipped = 0;
    private float $startTime;
    private string $currentSuite = '';

    public function __construct()
    {
        $this->startTime = microtime(true);
    }

    public function suite(string $name): self
    {
        $this->currentSuite = $name;
        return $this;
    }

    public function assert(string $testName, bool $condition, string $detail = ''): void
    {
        if ($condition) {
            $this->passed++;
            $status = 'PASS';
        } else {
            $this->failed++;
            $status = 'FAIL';
        }

        $this->results[] = [
            'suite'  => $this->currentSuite,
            'name'   => $testName,
            'status' => $status,
            'detail' => $detail,
            'time'   => microtime(true) - $this->startTime,
        ];
    }

    public function assertEquals($expected, $actual, string $testName): void
    {
        $ok = $expected === $actual;
        $detail = $ok ? '' : "Expected: " . json_encode($expected) . " | Got: " . json_encode($actual);
        $this->assert($testName, $ok, $detail);
    }

    public function assertContains($needle, $haystack, string $testName): void
    {
        if (is_string($haystack)) {
            $ok = str_contains($haystack, $needle);
        } elseif (is_array($haystack)) {
            $ok = in_array($needle, $haystack);
        } else {
            $ok = false;
        }
        $detail = $ok ? '' : "Expected to contain: " . json_encode($needle);
        $this->assert($testName, $ok, $detail);
    }

    public function assertNotEmpty($value, string $testName): void
    {
        $this->assert($testName, !empty($value), empty($value) ? "Value is empty" : '');
    }

    public function assertHttpStatus(int $expected, int $actual, string $testName): void
    {
        $detail = $expected !== $actual ? "Expected HTTP {$expected}, Got HTTP {$actual}" : '';
        $this->assert($testName, $expected === $actual, $detail);
    }

    public function skip(string $testName, string $reason = ''): void
    {
        $this->skipped++;
        $this->results[] = [
            'suite'  => $this->currentSuite,
            'name'   => $testName,
            'status' => 'SKIP',
            'detail' => $reason,
            'time'   => microtime(true) - $this->startTime,
        ];
    }

    /**
     * ส่ง HTTP request และวัดเวลา
     * @param string $contentType 'form' หรือ 'json'
     */
    public function httpRequest(string $method, string $url, array $data = [], array $files = [], string $contentType = 'form'): array
    {
        $ch = curl_init();
        $start = microtime(true);

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($contentType === 'json') {
                $jsonBody = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($jsonBody)]);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }

        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $duration = round((microtime(true) - $start) * 1000, 2); // ms
        $error    = curl_error($ch);
        curl_close($ch);

        $json = json_decode($body, true);

        return [
            'status'   => $httpCode,
            'body'     => $body,
            'json'     => $json,
            'duration' => $duration,
            'error'    => $error,
        ];
    }

    public function getResults(): array
    {
        return $this->results;
    }

    public function getSummary(): array
    {
        return [
            'passed'   => $this->passed,
            'failed'   => $this->failed,
            'skipped'  => $this->skipped,
            'total'    => $this->passed + $this->failed + $this->skipped,
            'duration' => round((microtime(true) - $this->startTime) * 1000, 2),
        ];
    }
}
