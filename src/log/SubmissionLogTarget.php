<?php
namespace verbb\formie\log;

use verbb\formie\helpers\LogPrivacy;

use Craft;
use craft\log\MonologTarget;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;
use Psr\Log\LogLevel;

/** Formie's plugin log, whose request context follows the `logSubmissionValues` setting. */
class SubmissionLogTarget extends MonologTarget
{
    // Static Methods
    // =========================================================================

    /**
     * Replaces the target Verbb Base registers, which can't be subclassed through its options.
     * The settings match Base's, so only the request context differs. Plugin settings aren't
     * available this early, which is why the setting is read when logs are exported.
     */
    public static function register(string $category = 'verbb\\formie\\*'): void
    {
        if ($dispatcher = Craft::getLogger()->dispatcher) {
            $monologTargetConfig = $dispatcher->monologTargetConfig;
            unset($monologTargetConfig['class']);

            $dispatcher->targets[$category] = new self(array_replace_recursive([
                'name' => 'formie',
                'categories' => [$category],
                'level' => LogLevel::INFO,
                'allowLineBreaks' => true,
                'logVars' => ['_GET', '_POST'],
                'formatter' => new LineFormatter(
                    format: "%datetime% [%level_name%] %message%\n",
                    dateFormat: 'Y-m-d H:i:s',
                    allowInlineLineBreaks: true,
                ),
            ], $monologTargetConfig));
        }
    }


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        parent::__construct($config);

        // Craft adds a raw request body to the record's context instead of `$_POST` for JSON
        // requests. Formie's format doesn't print context, but a site's own formatter may.
        $this->getLogger()->pushProcessor(function(LogRecord $record): LogRecord {
            if (!is_string($record->context['body'] ?? null)) {
                return $record;
            }

            return $record->with(context: ['body' => LogPrivacy::maskRequestBody($record->context['body'])] + $record->context);
        });
    }

    public function export(): void
    {
        if (!$this->logContext) {
            parent::export();

            return;
        }

        $post = $_POST;
        $get = $_GET;
        $formHandle = is_string($post['handle'] ?? null) ? $post['handle'] : null;

        // Craft's context processor reads these globals directly and can't be swapped out of
        // its target, so they hold masked copies only while this target writes its messages.
        $_POST = LogPrivacy::maskRequestParams($post, $formHandle);
        $_GET = LogPrivacy::maskRequestParams($get, $formHandle);

        try {
            parent::export();
        } finally {
            $_POST = $post;
            $_GET = $get;
        }
    }
}
