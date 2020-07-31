<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
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
        $request = Craft::$app->getRequest();
        $formId = $request->getParam('formId');

        // Create a new Notification model from this - it'll be a serialized array from Vue
        $notification = new Notification();
        $notification->setAttributes($request->getParam('notification'), false);

        $form = Formie::$plugin->getForms()->getFormById($formId);

        // Create a fake submission for this form.
        $submission = new Submission();
        $submission->setForm($form);

        // Populate all fields with fake content
        Formie::$plugin->getSubmissions()->populateFakeSubmission($submission);

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
}
