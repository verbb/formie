<?php
namespace verbb\formie\services;

use verbb\formie\controllers\SubmissionsController;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\enums\CompletionBehavior;
use verbb\formie\enums\RedirectSource;
use verbb\formie\enums\RedirectTarget;
use verbb\formie\events\SubmissionEvent;
use verbb\formie\helpers\CompletionRedirectPolicy;
use verbb\formie\helpers\References;
use verbb\formie\helpers\StringHelper;
use verbb\formie\helpers\SubmissionRedirectRulesHelper;
use verbb\formie\helpers\UrlHelper;
use verbb\formie\models\CompletionOutcome;

use Craft;

use yii\base\Component;

final class CompletionResolver extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_RESOLVE_COMPLETION = 'resolveCompletion';


    // Public Methods
    // =========================================================================

    public function resolve(Form $form, Submission $submission, bool $raiseEvents = true, bool $includeCapturedQuery = true): CompletionOutcome
    {
        $saved = $submission->getMetadata('completion');
        if ($raiseEvents && !$submission->isIncomplete && isset($saved['behavior'])) {
            return new CompletionOutcome(CompletionBehavior::from($saved['behavior']), $saved['url'], RedirectTarget::from($saved['target']), $saved['message'], $saved['hideForm']);
        }
        $settings = $form->settings;
        $behavior = CompletionBehavior::from($settings->completionBehavior);
        $source = RedirectSource::from($settings->completionRedirectSource);
        $url = $source === RedirectSource::Entry
            ? (string)($form->getRedirectEntry()?->url ?? '') : (string)$settings->redirectUrl;
        $rule = SubmissionRedirectRulesHelper::getMatchedRule($form, $submission);
        $resolved = false;
        if ($rule) {
            try {
                $url = SubmissionRedirectRulesHelper::resolveRuleUrl($rule, $form, $submission);
            } catch (\verbb\formie\references\ReferenceException $e) {
                $url = '';
            }
            $resolved = true;
            $behavior = CompletionBehavior::Redirect;
        }
        $override = $form->getCompletionRedirectOverride();
        if ($override !== null && $override !== '') {
            $url = $override;
            $resolved = false;
            $behavior = CompletionBehavior::Redirect;
        }
        if ($url !== '' && (preg_match('/[\x00-\x1f\x7f]/', $url) || str_contains($url, '{{') || str_contains($url, '{%'))) {
            $url = '';
        }
        if ($url !== '' && !$resolved) {
            try {
                $url = References::resolveUrl($url, $submission);
            } catch (\verbb\formie\references\ReferenceException $e) {
                $url = '';
            }
        }
        if ($raiseEvents) {
            foreach ($submission->getPayments() ?? [] as $payment) {
                if ($payment->status !== \verbb\formie\models\Payment::STATUS_SUCCESS) {
                    continue;
                }
                $candidate = $behavior === CompletionBehavior::Redirect ? $url : '';
                $paymentEvent = new \verbb\formie\events\PaymentSuccessRedirectEvent(['payment' => $payment, 'submission' => $submission, 'form' => $form, 'redirectUrl' => $candidate]);
                \verbb\formie\Formie::$plugin->getPayments()->trigger(Payments::EVENT_DEFINE_PAYMENT_SUCCESS_REDIRECT_URL, $paymentEvent);
                $paymentUrl = $paymentEvent->redirectUrl;
                if ($paymentUrl !== $candidate) {
                    $behavior = CompletionBehavior::Redirect;
                    $url = $paymentUrl;
                }
            }
        }
        $event = new SubmissionEvent(['form' => $form, 'submission' => $submission,
            'handle' => $form->handle, 'submitAction' => 'submit', 'success' => true, 'redirectUrl' => $url]);
        if ($raiseEvents) {
            $this->trigger(self::EVENT_RESOLVE_COMPLETION, $event);
            // Keep the source-verified Formie 3 PHP event, across every transport.
            $adapter = new SubmissionsController('submissions', \verbb\formie\Formie::$plugin);
            $adapter->trigger(SubmissionsController::EVENT_AFTER_SUBMISSION_REQUEST, $event);
        }
        if ($event->redirectUrl !== $url) {
            $behavior = CompletionBehavior::Redirect;
        }
        $url = CompletionRedirectPolicy::validate((string)$event->redirectUrl);
        if ($url !== '' && $includeCapturedQuery) {
            $url = CompletionRedirectPolicy::validate(UrlHelper::appendQueryParams($url, $form->getInstanceConfig()->query));
        }
        // Invalid trusted configuration fails closed to the normal completion message.
        if ($behavior === CompletionBehavior::Redirect && $url === '') {
            $behavior = CompletionBehavior::Message;
        }
        return new CompletionOutcome($behavior, $behavior === CompletionBehavior::Redirect ? $url : null,
            RedirectTarget::from($settings->redirectTarget),
            $behavior === CompletionBehavior::Message ? (StringHelper::sanitizeMessageHtml($settings->getSuccessMessage($submission)) ?: Craft::t('formie', 'Submission saved.')) : null,
            $settings->hideFormAfterSubmit);
    }
}
