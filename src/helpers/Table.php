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
    public const FORMIE_FORMS = '{{%formie_forms}}';
    public const FORMIE_FORM_TEMPLATES = '{{%formie_formtemplates}}';
    public const FORMIE_INTEGRATIONS = '{{%formie_integrations}}';
    public const FORMIE_NOTIFICATIONS = '{{%formie_notifications}}';
    public const FORMIE_PAYMENTS = '{{%formie_payments}}';
    public const FORMIE_PAYMENT_PLANS = '{{%formie_payments_plans}}';
    public const FORMIE_SUBSCRIPTIONS = '{{%formie_payments_subscriptions}}';
    public const FORMIE_PDF_TEMPLATES = '{{%formie_pdftemplates}}';
    public const FORMIE_RELATIONS = '{{%formie_relations}}';
    public const FORMIE_SENT_NOTIFICATIONS = '{{%formie_sentnotifications}}';
    public const FORMIE_STATUSES = '{{%formie_statuses}}';
    public const FORMIE_STENCILS = '{{%formie_stencils}}';
    public const FORMIE_SUBMISSIONS = '{{%formie_submissions}}';
}
