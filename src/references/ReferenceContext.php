<?php
namespace verbb\formie\references;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use Craft;
use craft\elements\User;
use craft\helpers\App;
use craft\models\Site;

use DateTimeImmutable;
use DateTimeZone;

/** Captures evaluation inputs at the boundary; the resolver never changes Craft's current site/user. */
final readonly class ReferenceContext
{
    // Static Methods
    // =========================================================================

    public static function forSubmission(Submission $submission, ?Notification $notification = null, array $rows = [], array $permissions = ['server'], ReferenceOutputContext $outputContext = ReferenceOutputContext::PlainText): self
    {
        $site = Craft::$app->getSites()->getSiteById($submission->siteId);
        $user = $submission->getUser();
        $mail = App::mailSettings();
        $overrides = $mail->siteOverrides[$site?->uid] ?? [];
        $system = [];
        foreach (['name' => 'fromName', 'email' => 'fromEmail', 'replyTo' => 'replyToEmail'] as $name => $attribute) {
            $system[$name] = $overrides[$attribute] ?? $mail->$attribute;
        }
        $dispatch = [];
        foreach (Formie::$plugin->getIntegrationDispatcher()->loadContext($submission)->results as $handle => $result) {
            if (is_array($result)) {
                $dispatch[$handle] = ['id' => $result['elementId'] ?? null, 'url' => $result['url'] ?? null, 'success' => $result['success'] ?? false, 'type' => $result['type'] ?? null];
            }
        }
        $environment = [];
        foreach (Formie::$plugin->getSettings()->referenceEnvironmentAllowlist as $name) {
            if (is_string($name) && preg_match('/^[A-Z][A-Z0-9_]*$/D', $name)) {
                $environment[$name] = App::env($name);
            }
        }
        return new self($submission->getForm(), $submission, $site, $user, $rows, $permissions, $outputContext, $notification, new DateTimeImmutable('now', new DateTimeZone(Craft::$app->getTimeZone())), $system, $environment, $dispatch, metadata: $submission->metadata ?? []);
    }


    // Public Methods
    // =========================================================================

    public ReferenceDiagnostics $diagnostics;

    public function __construct(
        public ?Form $form = null,
        public ?Submission $submission = null,
        public ?Site $site = null,
        public ?User $user = null,
        public array $rows = [],
        public array $permissions = [],
        public ReferenceOutputContext $outputContext = ReferenceOutputContext::PlainText,
        public ?Notification $notification = null,
        public ?DateTimeImmutable $now = null,
        public array $system = [],
        public array $environment = [],
        public array $dispatch = [],
        public array $report = [],
        public array $metadata = [],
        ?ReferenceDiagnostics $diagnostics = null,
    ) {
        $this->diagnostics = $diagnostics ?? new ReferenceDiagnostics();

        if ($submission && $form && $submission->formId !== $form->id) {
            throw new \InvalidArgumentException('Reference form and submission must share an owner.');
        }
    }
}
