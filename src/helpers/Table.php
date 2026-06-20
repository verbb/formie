<?php
namespace verbb\formie\helpers;

use craft\db\Table as CraftTable;

abstract class Table extends CraftTable
{
    // Constants
    // =========================================================================

    public const FORMIE_EMAIL_TEMPLATES = '{{%formie_emailtemplates}}';
    public const FORMIE_FIELD_LAYOUT_PAGES = '{{%formie_fieldlayout_pages}}';
    public const FORMIE_FIELD_LAYOUT_ROWS = '{{%formie_fieldlayout_rows}}';
    public const FORMIE_FIELD_LAYOUTS = '{{%formie_fieldlayouts}}';
    public const FORMIE_FIELDS = '{{%formie_fields}}';
    public const FORMIE_FORM_FIELDS = '{{%formie_form_fields}}';
    public const FORMIE_FORMS = '{{%formie_forms}}';
    public const FORMIE_FORM_TEMPLATES = '{{%formie_formtemplates}}';
    public const FORMIE_FORM_GROUPS = '{{%formie_formgroups}}';
    public const FORMIE_INTEGRATIONS = '{{%formie_integrations}}';
    public const FORMIE_CAPTCHA_PROVIDERS = '{{%formie_captcha_providers}}';
    public const FORMIE_SPAM_SETTINGS = '{{%formie_spam_settings}}';
    public const FORMIE_NOTIFICATIONS = '{{%formie_notifications}}';
    public const FORMIE_PAYMENTS = '{{%formie_payments}}';
    public const FORMIE_PAYMENT_PLANS = '{{%formie_payments_plans}}';
    public const FORMIE_SUBSCRIPTIONS = '{{%formie_payments_subscriptions}}';
    public const FORMIE_PDF_TEMPLATES = '{{%formie_pdftemplates}}';
    public const FORMIE_RELATIONS = '{{%formie_relations}}';
    public const FORMIE_SENT_NOTIFICATIONS = '{{%formie_sentnotifications}}';
    public const FORMIE_STATUSES = '{{%formie_statuses}}';
    public const FORMIE_STENCILS = '{{%formie_stencils}}';
    public const FORMIE_PENDING_UPLOADS = '{{%formie_pending_uploads}}';
    public const FORMIE_REPORTS = '{{%formie_reports}}';
    public const FORMIE_SCHEDULED_REPORTS = '{{%formie_scheduled_reports}}';
    public const FORMIE_REPORT_EXPORTS = '{{%formie_report_exports}}';
    public const FORMIE_SUBMISSION_RESUME_TOKENS = '{{%formie_submission_resume_tokens}}';
    public const FORMIE_SUBMISSION_WORKFLOW = '{{%formie_submission_workflow}}';
    public const FORMIE_SUBMISSIONS = '{{%formie_submissions}}';
    public const FORMIE_SUBMISSION_QUIZ_RESULTS = '{{%formie_submission_quiz_results}}';
    public const FORMIE_SUBMISSION_DRAFTS = '{{%formie_submission_drafts}}';
    public const FORMIE_FORM_SITE_OVERRIDES = '{{%formie_form_site_overrides}}';
}
