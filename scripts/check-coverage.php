<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$report = $root . '/build/coverage.xml';

if (!is_file($report)) {
    fwrite(STDERR, 'Coverage report not found at ' . $report . '. Run "composer coverage" to generate it.' . PHP_EOL);

    exit(1);
}

$xml = simplexml_load_file($report);
if ($xml === false) {
    fwrite(STDERR, 'Unable to parse the coverage report at ' . $report . '.' . PHP_EOL);

    exit(1);
}

$metrics = $xml->project->metrics;
$threshold = (float) ($argv[1] ?? 95.0);

$lines = (int) $metrics['statements'];
$coveredLines = (int) $metrics['coveredstatements'];
$methods = (int) $metrics['methods'];
$coveredMethods = (int) $metrics['coveredmethods'];

$linePercent = $lines > 0 ? $coveredLines / $lines * 100 : 100.0;
$methodPercent = $methods > 0 ? $coveredMethods / $methods * 100 : 100.0;

printf(
    "Coverage: %.2f%% lines (%d/%d), %.2f%% methods (%d/%d). Threshold: %.2f%% lines.%s",
    $linePercent,
    $coveredLines,
    $lines,
    $methodPercent,
    $coveredMethods,
    $methods,
    $threshold,
    PHP_EOL,
);

if ($linePercent < $threshold) {
    fwrite(
        STDERR,
        sprintf("Coverage %.2f%% is below the %.2f%% threshold. Failing the check.%s", $linePercent, $threshold, PHP_EOL),
    );

    exit(1);
}

exit(0);
