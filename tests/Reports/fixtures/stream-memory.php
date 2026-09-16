<?php

require dirname(__DIR__, 2) . '/runtime/bootstrap.php';
$app = require CRAFT_VENDOR_PATH . '/craftcms/cms/bootstrap/console.php';

use verbb\formie\helpers\ReportExportWriter;

$format = $argv[1] ?? 'xlsx';
$count = (int)($argv[2] ?? 100000);
$path = tempnam(sys_get_temp_dir(), 'formie-stream-memory-');
$headers = array_map(fn($i) => 'Column ' . $i, range(1, 10));
$rows = (function () use ($count, $headers) {
    for ($i = 0; $i < $count; $i++) {
        yield array_combine($headers, array_map(fn($column) => sprintf('%08d %s %s', $i, $column, str_repeat('value', 8)), $headers));
    }
})();
$start = microtime(true);
try {
    ReportExportWriter::write($path, $format, $headers, $rows);
    echo json_encode(['format' => $format, 'rows' => $count, 'columns' => count($headers), 'bytes' => filesize($path), 'peakBytes' => memory_get_peak_usage(true), 'seconds' => round(microtime(true) - $start, 2)]) . PHP_EOL;
} finally { @unlink($path); }
