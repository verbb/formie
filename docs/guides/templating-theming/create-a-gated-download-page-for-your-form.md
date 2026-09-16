# Create a Gated Download Page for Your Form

This guide gives signed-in visitors a private PDF after they complete a resource-request form. A Craft controller checks the account and submission on every download, then streams the file from private storage. Copying the download link to another account will not grant access.

You need Craft user accounts with an existing sign-in page, permission to create Formie forms and asset volumes, and access to your project's PHP files. This example is for a free resource. Paid downloads also need an entitlement policy covering the correct product, amount, currency, refunds and payment state; a successful payment on an arbitrary submission is not enough.

## Prepare the Form and Private File

Create a form named **Resource Request**, with handle `resourceRequest`. Add a required Email Address field with handle `emailAddress`. In its settings, enable **Collect User** under **Settings → Privacy** (`collectUser`), choose a URL submit action and enter `/resource-ready`. Save the form. The owner must come from the signed-in Craft account, not the email typed into the form.

Create an asset volume with handle `privateDownloads`, backed by a filesystem without public URLs. For local storage, place the files outside the web root; for object storage, deny public access. Upload a PDF and note its asset ID. The example below uses `10839`; replace it with your PDF's ID.

A public asset URL remains accessible independently of your form. Check that the underlying file cannot be fetched directly before proceeding.

## Require Sign-In Before Submission

Create `templates/request-resource.twig`:

```twig
{% requireLogin %}
{% header "Cache-Control: private, no-store" %}
{{ craft.formie.renderForm('resourceRequest') }}
```

Open `/request-resource` while signed out. Craft should send you to your configured login page. Sign in and confirm the form appears. The form's saved settings must collect the current user so that its submissions can be matched to that account.

## Add a Download Controller

The controller uses a fixed form, volume and asset. Visitors cannot choose another file by changing a request parameter.

In the Craft project's `composer.json`, merge this namespace into `autoload.psr-4`, preserving existing mappings:

```json
{
    "autoload": {
        "psr-4": {
            "modules\\downloads\\": "modules/downloads/"
        }
    }
}
```

Run `composer dump-autoload` in the Craft project root. In `config/app.php`, merge this module into the existing `modules` array:

```php
<?php

return [
    'modules' => [
        'downloads' => [
            'class' => yii\base\Module::class,
            'controllerNamespace' => 'modules\\downloads\\controllers',
        ],
    ],
];
```

Create `modules/downloads/controllers/ResourceController.php` with the complete controller below:

```php
<?php
namespace modules\downloads\controllers;

use Craft;
use craft\elements\Asset;
use craft\web\Controller;
use verbb\formie\elements\Submission;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ResourceController extends Controller
{
    public function actionDownload(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $user = Craft::$app->getUser()->getIdentity();
        $submission = Submission::find()
            ->form('resourceRequest')
            ->siteId(Craft::$app->getSites()->getCurrentSite()->id)
            ->userId($user->id)
            ->isIncomplete(false)
            ->isSpam(false)
            ->one();

        if (!$submission) {
            throw new NotFoundHttpException();
        }

        $asset = Asset::find()->volume('privateDownloads')->id(10839)->one();

        if (!$asset || $asset->getVolume()->getFs()->hasUrls) {
            throw new NotFoundHttpException();
        }

        $response = Craft::$app->getResponse();
        $response->getHeaders()->set('Cache-Control', 'private, no-store');

        return $response->sendStreamAsFile($asset->getStream(), $asset->getFilename(), [
            'fileSize' => $asset->size,
            'mimeType' => $asset->getMimeType(),
            'inline' => false,
        ]);
    }
}
```

Craft's controller keeps CSRF protection enabled. The query requires a completed, non-spam submission to this form on this site, owned by the current user. It grants repeat downloads while that qualifying submission exists. Choose a different entitlement model if access must expire or survive submission retention cleanup.

## Create the Download Page

Create `templates/resource-ready.twig`:

```twig
{% requireLogin %}
{% header "Cache-Control: private, no-store" %}

<h1>Your Resource Is Ready</h1>
<p>Download your copy of the resource below.</p>

<form method="post">
    {{ csrfInput() }}
    {{ actionInput('downloads/resource/download') }}
    <button type="submit">Download PDF</button>
</form>
```

This page can be opened by any signed-in account, but the controller sends the file only to an account with a qualifying submission. Exclude `/request-resource`, `/resource-ready` and the download action from full-page or CDN caching. Keep the private filesystem inaccessible over a direct URL.

## Test the Complete Journey

Sign in as a test user, open `/request-resource`, submit the form and download the PDF from `/resource-ready`. In **Formie → Submissions**, check that the completed record belongs to the signed-in account. If the controller returns 404, first check the form handle, site, asset ID, volume handle and collected user.

Then sign in as another account that has not completed the form. Opening the same page and pressing **Download PDF** must not return the file. Also test while signed out and confirm that login is required. A missing CSRF token must prevent the POST. Finally, verify that a direct storage URL cannot bypass the controller.

Submitting someone else's email must not grant their access: ownership comes from the Craft session. Do not replace the account check with an email-address lookup or a submission identifier supplied by the visitor.

For a public confirmation without restricted files, see [Build a Success Page](/guides/templating-theming/build-a-success-page-for-your-form).
