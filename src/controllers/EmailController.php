<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;
use verbb\formie\models\Notification;

use Craft;
use craft\web\Controller;

class EmailController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPreview()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $notification = new Notification();
        $submission = new Submission();

        // Populate the submission and notification
        $this->_populateFromPost($notification, $submission);

        $emailRender = Formie::$plugin->getEmails()->renderEmail($notification, $submission);

        if (isset($emailRender['error']) && $emailRender['error']) {
            return $this->asJson([
                'error' => $emailRender['error'],
            ]);
        }

        $email = $emailRender['email'];

        $htmlBody = $email->getSwiftMessage()->getBody();
        $children = $email->getSwiftMessage()->getChildren();

        // Getting the content from an email is a little more involved...
        if (!$htmlBody && $children) {
            foreach ($children as $child) {
                if ($child->getContentType() == 'text/html') {
                    $htmlBody = $child->getBody();
                }
            }
        }

        return $this->asJson([
            'from' => $email->getFrom(),
            'to' => $email->getTo(),
            'bcc' => $email->getBcc(),
            'cc' => $email->getCc(),
            'replyTo' => $email->getReplyTo(),
            'subject' => $email->getSubject(),
            'body' => $htmlBody,
        ]);
    }

    public function actionSendTestEmail()
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $notification = new Notification();
        $submission = new Submission();

        // Populate the submission and notification
        $this->_populateFromPost($notification, $submission);

        // Override the 'to' field
        $notification->to = $request->getParam('to');

        $sentResponse = Formie::$plugin->getEmails()->sendEmail($notification, $submission);
        $success = $sentResponse['success'] ?? false;
        $error = $sentResponse['error'] ?? false;

        return $this->asJson([
            'success' => $success,
            'error' => $error,
        ]);
    }


    // Private Methods
    // =========================================================================

    private function _populateFromPost($notification, $submission)
    {
        $request = Craft::$app->getRequest();
        $formId = $request->getParam('formId');
        $handle = $request->getParam('handle');

        // Create a new Notification model from this - it'll be a serialized array from Vue
        $notification->setAttributes($request->getParam('notification'), false);

        // If a stencil, creata a fake form
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

        return $submission;
    }
}
