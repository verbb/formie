<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use Craft;
use craft\helpers\StringHelper;
use craft\web\Controller;

use yii\web\Response;

class EmailController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPreview(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $notification = new Notification();
        $submission = new Submission();

        // Populate the submission and notification
        $this->_populateFromPost($notification, $submission);

        $emailRender = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

        if (isset($emailRender['error']) && $emailRender['error']) {
            Formie::error($emailRender['error']);

            // Output the full exception if available
            if (isset($emailRender['exception']) && $emailRender['exception']) {
                Formie::error($emailRender['exception']);
            }

            return $this->asJson([
                'error' => $emailRender['error'],
            ]);
        }

        $email = $emailRender['email'];

        return $this->asJson([
            'from' => $email->getFrom(),
            'to' => $email->getTo(),
            'bcc' => $email->getBcc(),
            'cc' => $email->getCc(),
            'sender' => $email->getSender(),
            'replyTo' => $email->getReplyTo(),
            'subject' => $email->getSubject(),
            'body' => $email->getHtmlBody(),
        ]);
    }

    public function actionSendTestEmail(): Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $notification = new Notification();
        $submission = new Submission();

        // Populate the submission and notification
        $this->_populateFromPost($notification, $submission);

        // Override the 'to' field
        $notification->to = $request->getParam('to');

        $sentResponse = Formie::$plugin->getEmails()->sendEmail($notification, $submission, null, false);
        $success = $sentResponse['success'] ?? false;
        $error = $sentResponse['error'] ?? false;

        return $this->asJson([
            'success' => $success,
            'error' => $error,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _populateFromPost($notification, $submission): void
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getParam('formId');
        $handle = $request->getParam('handle');

        // Create a new Notification model from this - it'll be a serialized array from Vue
        if ($notificationParams = $request->getParam('notification')) {
            $notification->setAttributes($notificationParams, false);
        }

        // Ensure some settings are type-cast
        $notification->enabled = StringHelper::toBoolean((string)$notification->enabled);
        $notification->attachFiles = StringHelper::toBoolean((string)$notification->attachFiles);
        $notification->attachPdf = StringHelper::toBoolean((string)$notification->attachPdf);
        $notification->enableConditions = StringHelper::toBoolean((string)$notification->enableConditions);

        // If a stencil, create a fake form
        if (!$formId) {
            $form = new Form();
            $stencil = Formie::$plugin->getStencils()->getStencilByHandle($handle);

            Formie::$plugin->getStencils()->applyStencil($form, $stencil);
        } else {
            $form = Formie::$plugin->getForms()->getFormById($formId);
        }

        // Create a fake submission for this form.
        $submission->setForm($form);

        // Populate all fields with fake content
        Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);
    }
}
