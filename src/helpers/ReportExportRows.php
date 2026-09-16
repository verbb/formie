<?php
namespace verbb\formie\helpers;

use verbb\formie\Formie;

use Craft;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQueryInterface;

use Generator;
use PDO;
use RuntimeException;
use Throwable;

class ReportExportRows
{
    // Static Methods
    // =========================================================================

    public static function iterate(ElementQueryInterface $query, array $columns, array $display, int $chunkSize, ?callable $progress = null): Generator
    {
        if ($chunkSize < 1) {
            throw new RuntimeException('Export chunk size must be positive.');
        }

        // Snapshot membership and ordering before formatting starts. Newly arriving
        // submissions cannot shift offsets or enter an export halfway through it.
        [$snapshot, $count] = self::_snapshotIds($query);
        $processed = 0;

        try {
            while (!feof($snapshot)) {
                $ids = [];

                while (count($ids) < $chunkSize && ($line = fgets($snapshot)) !== false) {
                    $ids[] = (int)trim($line);
                }

                if (!$ids) {
                    break;
                }

                // Recheck the original filters when hydrating: removed or no
                // longer eligible submissions must not leak through the snapshot.
                $batch = clone $query;
                $submissions = Craft::$app->getDb()->useMaster(fn() => $batch->id($ids)->fixedOrder(true)->limit(null)->offset(null)->all());

                foreach ($submissions as $submission) {
                    yield Formie::$plugin->getReportColumns()->formatRowAssoc($submission, $columns, $display);
                }

                $processed += count($ids);
                unset($submissions, $batch);
                gc_collect_cycles();

                if ($progress) {
                    $progress($count ? min(1, $processed / $count) : 1);
                }
            }

            if ($progress && !$count) {
                $progress(1);
            }
        } finally {
            fclose($snapshot);
        }
    }

    private static function _snapshotIds(ElementQueryInterface $query): array
    {
        $snapshot = tmpfile();

        if ($snapshot === false) {
            throw new RuntimeException('Unable to create export snapshot.');
        }

        $db = Craft::$app->getDb();
        $count = 0;

        try {
            $db->useMaster(function() use ($db, $query, $snapshot, &$count): void {
                $ids = clone $query;
                $ids->select(['elements.id'])->limit(null)->offset(null);
                $reader = null;
                $pdo = $db->getMasterPdo();
                $buffered = $db->getDriverName() === 'mysql' ? $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) : null;

                try {
                    // No nested database queries run while this cursor is open.
                    // Avoid PDO retaining every matching ID on MySQL as well.
                    if ($buffered !== null) {
                        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                    }

                    $reader = $ids->createCommand($db)->query();

                    while (($row = $reader->read()) !== false) {
                        $line = (int)$row['id'] . "\n";

                        if (fwrite($snapshot, $line) !== strlen($line)) {
                            throw new RuntimeException('Unable to write export snapshot.');
                        }

                        $count++;
                    }
                } catch (QueryAbortedException) {
                    // An impossible element scope is a valid empty export.
                } finally {
                    $reader?->close();

                    if ($buffered !== null) {
                        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered);
                    }
                }
            });

            if (!rewind($snapshot)) {
                throw new RuntimeException('Unable to read export snapshot.');
            }

            return [$snapshot, $count];
        } catch (Throwable $e) {
            fclose($snapshot);
            throw $e;
        }
    }
}
