# Custom client event templates

Formie ships built-in client event templates for GTM, GA4, Meta, and a blank starter. Plugins and project modules can register their own presets so content authors pick consistent analytics events from the form builder **Tracking** tab — without copying JSON between forms.

This guide covers author workflow and a real-world registration example. For template properties, payload rows, materialized event shape, programmatic access, and troubleshooting, see [Custom Client Event Templates](/developers/custom-client-event-templates).

## Prerequisites

- A Craft [module](https://craftcms.com/docs/5.x/extend/module-guide.html)
- [Custom Client Event Templates](/developers/custom-client-event-templates) reference
- [Tracking and Analytics](/frontend/tracking-and-analytics) — how events fire on submit

## Register a template

Listen for `ClientEventTemplates::EVENT_REGISTER_CLIENT_EVENT_TEMPLATES` in your module's `init()` method and push template definitions onto the event. See [Register a template](/developers/custom-client-event-templates#register-a-template) for the full registration snippet, property table, and payload contract.

When an author picks a template, Formie materializes it into a normal page client event. Authors can edit the event name, payload, and conditions afterwards.

## How authors use templates

1. Open a form in the builder and select a page.
2. Go to the **Tracking** tab.
3. Choose **Add event** — registered templates appear grouped by `categoryLabel`.
4. Templates with **field mapping** rows open a dialog so the author picks which form field supplies each payload key.
5. Templates listed under **Suggested for this page** match the page's `pageContexts` (`first-page`, `last-page`, `single-page`, and so on).

Authors can change the materialized event like any manually created client event — templates are starting points, not locked presets.

## Example — SaaS trial signup template

A plugin registering a trial-signup preset for product analytics:

```php
use Craft;
use verbb\formie\events\RegisterClientEventTemplatesEvent;
use verbb\formie\services\ClientEventTemplates;
use yii\base\Event;

Event::on(ClientEventTemplates::class, ClientEventTemplates::EVENT_REGISTER_CLIENT_EVENT_TEMPLATES, function(RegisterClientEventTemplatesEvent $event) {
    $event->templates[] = [
        'handle' => 'myplugin_trial_signup',
        'label' => Craft::t('my-plugin', 'Trial signup (Segment)'),
        'category' => 'myplugin',
        'categoryLabel' => Craft::t('my-plugin', 'Product analytics'),
        'event' => 'Trial Signup Completed',
        'pageContexts' => ['last-page', 'single-page'],
        'payload' => [
            ['key' => 'plan', 'value' => 'trial', 'kind' => 'static'],
            ['key' => 'form_id', 'value' => '{form:handle}', 'kind' => 'static'],
            [
                'key' => 'email',
                'value' => '',
                'kind' => 'field',
                'fieldTypes' => ['email'],
                'required' => true,
            ],
            [
                'key' => 'company',
                'value' => '',
                'kind' => 'field',
                'fieldTypes' => ['single-line-text'],
                'mappingLabel' => Craft::t('my-plugin', 'Company name field'),
            ],
        ],
    ];
});
```

Authors map fields once per form; Segment/GTM wiring stays in the tag manager. For static vs field-mapping rows and `pageContexts`, see [Payload rows](/developers/custom-client-event-templates#payload-rows) and [Page contexts](/developers/custom-client-event-templates#page-contexts).

## Finishing up

1. Hard-refresh the control panel after changing plugin code.
2. Open a form's **Tracking** tab and confirm the template appears under **Add event**.
3. Insert the template on a final page, submit the form, and confirm resolved events in the browser (`dataLayer`) or the Ajax/headless response.

If something does not appear, work through [Troubleshooting](/developers/custom-client-event-templates#troubleshooting) on the developer reference.

## Related

- [Custom Client Event Templates](/developers/custom-client-event-templates)
- [Tracking and Analytics](/frontend/tracking-and-analytics)
- [Client events — GTM, GA4, and Meta](/guides/frontend-headless/client-events-gtm-ga4-and-meta)
