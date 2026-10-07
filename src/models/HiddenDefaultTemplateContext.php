<?php
namespace verbb\formie\models;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use Craft;
use craft\base\Model;
use craft\elements\User;

class HiddenDefaultTemplateContext extends Model
{
    // Static Methods
    // =========================================================================

    public static function fromFieldContext(?Form $form, ?Submission $submission = null): self
    {
        $context = new self();

        if ($form) {
            $context->form->handle = (string)$form->handle;
            $context->form->title = (string)$form->title;
        }

        $context->submission = $submission;

        $site = Craft::$app->getSites()->getCurrentSite();
        $context->site->id = (int)$site->id;
        $context->site->handle = (string)$site->handle;
        $context->site->name = (string)$site->name;

        if (!Craft::$app->getRequest()->getIsConsoleRequest()) {
            $request = Craft::$app->getRequest();
            $context->currentUser = Craft::$app->getUser()->getIdentity();
            $context->request->param = $request->getQueryParams();
            $context->request->userIp = (string)$request->getUserIP();
            $context->request->absoluteUrl = (string)$request->getAbsoluteUrl();
            $context->request->userAgent = (string)$request->getUserAgent();
        }

        return $context;
    }


    // Properties
    // =========================================================================

    public HiddenDefaultTemplateFormContext $form;
    public ?Submission $submission = null;
    public ?User $currentUser = null;
    public HiddenDefaultTemplateSiteContext $site;
    public HiddenDefaultTemplateRequestContext $request;


    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        $this->form ??= new HiddenDefaultTemplateFormContext();
        $this->site ??= new HiddenDefaultTemplateSiteContext();
        $this->request ??= new HiddenDefaultTemplateRequestContext();
    }
}
