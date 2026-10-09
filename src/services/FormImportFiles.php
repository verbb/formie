<?php
namespace verbb\formie\services;

use verbb\formie\Formie;
use verbb\formie\helpers\StringHelper;

use craft\base\FsInterface;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class FormImportFiles
{
    // Constants
    // =========================================================================

    public const EXPIRY_SECONDS = 86400;

    private const DIRECTORY = 'formie-imports';
    private const FILENAME_PATTERN = '/^formie-import-[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.json$/D';


    // Public Methods
    // =========================================================================

    public function __construct(
        private FsInterface $filesystem,
        private int $expirySeconds = self::EXPIRY_SECONDS,
    ) {
    }

    public function store($stream, int $userId): string
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Form import content must be provided as a stream.');
        }

        $this->pruneExpired($userId);

        $directory = $this->_getUserDirectory($userId);

        if (!$this->filesystem->directoryExists($directory)) {
            $this->filesystem->createDirectory($directory);
        }

        $filename = 'formie-import-' . StringHelper::UUID() . '.json';
        $this->filesystem->writeFileFromStream($this->getStoragePath($filename, $userId), $stream);

        return $filename;
    }

    public function read(mixed $filename, int $userId): ?string
    {
        $path = $this->getStoragePath($filename, $userId);

        if (!$this->filesystem->fileExists($path)) {
            return null;
        }

        return $this->filesystem->read($path);
    }

    public function delete(mixed $filename, int $userId): void
    {
        $path = $this->getStoragePath($filename, $userId);

        if ($this->filesystem->fileExists($path)) {
            $this->filesystem->deleteFile($path);
        }
    }

    public function getStoragePath(mixed $filename, int $userId): string
    {
        if (!is_string($filename) || !preg_match(self::FILENAME_PATTERN, $filename)) {
            throw new InvalidArgumentException('Invalid import filename.');
        }

        return $this->_getUserDirectory($userId) . '/' . $filename;
    }

    public function pruneExpired(int $userId, ?int $now = null): void
    {
        $directory = $this->_getUserDirectory($userId);

        try {
            if (!$this->filesystem->directoryExists($directory)) {
                return;
            }

            $cutoff = ($now ?? time()) - $this->expirySeconds;

            foreach ($this->filesystem->getFileList($directory) as $listing) {
                if ($listing->getIsDir() || !preg_match(self::FILENAME_PATTERN, $listing->getBasename())) {
                    continue;
                }

                $dateModified = $listing->getDateModified() ?? $this->filesystem->getDateModified($listing->getUri());

                if ($dateModified < $cutoff) {
                    $this->filesystem->deleteFile($listing->getUri());
                }
            }
        } catch (Throwable $e) {
            // Cleanup must never prevent a new import from being uploaded.
            Formie::warning('Unable to prune expired form import files: {message}', [
                'message' => $e->getMessage(),
            ]);
        }
    }


    // Private Methods
    // =========================================================================

    private function _getUserDirectory(int $userId): string
    {
        if ($userId <= 0) {
            throw new RuntimeException('A signed-in user is required to store form imports.');
        }

        return self::DIRECTORY . '/' . $userId;
    }
}
