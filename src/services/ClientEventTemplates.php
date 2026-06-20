<?php
namespace verbb\formie\services;

use verbb\formie\events\RegisterClientEventTemplatesEvent;
use verbb\formie\Formie;

use Craft;
use craft\base\Component;

class ClientEventTemplates extends Component
{
    // Constants
    // =========================================================================

    public const EVENT_REGISTER_CLIENT_EVENT_TEMPLATES = 'registerClientEventTemplates';


    // Properties
    // =========================================================================

    private array $_templates = [];


    // Public Methods
    // =========================================================================

    public function getRegisteredTemplates(): array
    {
        if ($this->_templates !== []) {
            return $this->_templates;
        }

        $templates = [
            $this->_defineTemplate([
                'handle' => 'gtm-page-submit',
                'label' => Craft::t('formie', 'GTM — Page Submit'),
                'description' => Craft::t('formie', 'Push form metadata after each successful page submit.'),
                'category' => 'gtm',
                'categoryLabel' => Craft::t('formie', 'Google Tag Manager'),
                'event' => 'formPageSubmission',
                'pageContexts' => ['any'],
                'payload' => [
                    ['key' => 'formHandle', 'value' => '{form:handle}', 'kind' => 'static'],
                    ['key' => 'formName', 'value' => '{form:name}', 'kind' => 'static'],
                ],
            ]),
            $this->_defineTemplate([
                'handle' => 'gtm-form-step',
                'label' => Craft::t('formie', 'GTM — Form Step'),
                'description' => Craft::t('formie', 'Track multi-page progress after each step completes.'),
                'category' => 'gtm',
                'categoryLabel' => Craft::t('formie', 'Google Tag Manager'),
                'event' => 'formStep',
                'pageContexts' => ['first-page', 'middle-page'],
                'payload' => [
                    ['key' => 'formHandle', 'value' => '{form:handle}', 'kind' => 'static'],
                    ['key' => 'formName', 'value' => '{form:name}', 'kind' => 'static'],
                ],
            ]),
            $this->_defineTemplate([
                'handle' => 'ga4-form-start',
                'label' => Craft::t('formie', 'GA4 — Form Start'),
                'description' => Craft::t('formie', 'Recommended for the first page of a multi-page form.'),
                'category' => 'ga4',
                'categoryLabel' => Craft::t('formie', 'Google Analytics 4'),
                'event' => 'form_start',
                'pageContexts' => ['first-page', 'single-page'],
                'payload' => [
                    ['key' => 'form_id', 'value' => '{form:handle}', 'kind' => 'static'],
                    ['key' => 'form_name', 'value' => '{form:name}', 'kind' => 'static'],
                ],
            ]),
            $this->_defineTemplate([
                'handle' => 'ga4-generate-lead',
                'label' => Craft::t('formie', 'GA4 — Generate Lead'),
                'description' => Craft::t('formie', 'Recommended for the final page when a lead is captured.'),
                'category' => 'ga4',
                'categoryLabel' => Craft::t('formie', 'Google Analytics 4'),
                'event' => 'generate_lead',
                'pageContexts' => ['last-page', 'single-page'],
                'payload' => [
                    ['key' => 'form_id', 'value' => '{form:handle}', 'kind' => 'static'],
                    ['key' => 'form_name', 'value' => '{form:name}', 'kind' => 'static'],
                    [
                        'key' => 'email',
                        'value' => '',
                        'kind' => 'field',
                        'fieldTypes' => ['email'],
                        'mappingLabel' => Craft::t('formie', 'Email field'),
                        'required' => true,
                    ],
                    [
                        'key' => 'value',
                        'value' => '',
                        'kind' => 'field',
                        'fieldTypes' => ['number', 'calculations', 'single-line-text'],
                        'mappingLabel' => Craft::t('formie', 'Value field'),
                        'required' => false,
                    ],
                    ['key' => 'currency', 'value' => 'USD', 'kind' => 'static'],
                ],
            ]),
            $this->_defineTemplate([
                'handle' => 'meta-lead',
                'label' => Craft::t('formie', 'Meta — Lead'),
                'description' => Craft::t('formie', 'Recommended for the final page of lead-generation forms.'),
                'category' => 'meta',
                'categoryLabel' => Craft::t('formie', 'Meta'),
                'event' => 'Lead',
                'pageContexts' => ['last-page', 'single-page'],
                'payload' => [
                    ['key' => 'content_name', 'value' => '{form:name}', 'kind' => 'static'],
                    [
                        'key' => 'email',
                        'value' => '',
                        'kind' => 'field',
                        'fieldTypes' => ['email'],
                        'mappingLabel' => Craft::t('formie', 'Email field'),
                        'required' => true,
                    ],
                ],
            ]),
            $this->_defineTemplate([
                'handle' => 'blank',
                'label' => Craft::t('formie', 'Blank Event'),
                'description' => Craft::t('formie', 'Start with an empty event and payload.'),
                'category' => 'general',
                'categoryLabel' => Craft::t('formie', 'General'),
                'event' => 'formPageSubmission',
                'pageContexts' => ['any'],
                'payload' => [],
            ]),
        ];

        $event = new RegisterClientEventTemplatesEvent([
            'templates' => $templates,
        ]);

        $this->trigger(self::EVENT_REGISTER_CLIENT_EVENT_TEMPLATES, $event);

        foreach ($event->templates as $template) {
            if (!is_array($template) || empty($template['handle'])) {
                continue;
            }

            $this->_templates[(string)$template['handle']] = $this->_normalizeTemplate($template);
        }

        return $this->_templates;
    }

    public function getTemplate(string $handle): ?array
    {
        return $this->getRegisteredTemplates()[$handle] ?? null;
    }

    public function getBuilderConfig(): array
    {
        $templates = [];

        foreach ($this->getRegisteredTemplates() as $template) {
            $templates[] = [
                'handle' => $template['handle'],
                'label' => $template['label'],
                'description' => $template['description'],
                'category' => $template['category'],
                'categoryLabel' => $template['categoryLabel'],
                'event' => $template['event'],
                'pageContexts' => $template['pageContexts'],
                'payload' => $template['payload'],
            ];
        }

        usort($templates, function(array $a, array $b): int {
            $aIsBlank = ($a['handle'] ?? '') === 'blank';
            $bIsBlank = ($b['handle'] ?? '') === 'blank';

            if ($aIsBlank !== $bIsBlank) {
                return $aIsBlank ? -1 : 1;
            }

            $categoryCompare = strcmp((string)$a['categoryLabel'], (string)$b['categoryLabel']);

            if ($categoryCompare !== 0) {
                return $categoryCompare;
            }

            return strcmp((string)$a['label'], (string)$b['label']);
        });

        return $templates;
    }

    public function materializeTemplate(string $handle, array $fieldMappings = []): ?array
    {
        $template = $this->getTemplate($handle);

        if (!$template) {
            return null;
        }

        $payload = [];

        foreach ($template['payload'] as $row) {
            $key = trim((string)($row['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $kind = (string)($row['kind'] ?? 'static');
            $value = (string)($row['value'] ?? '');

            if ($kind === 'field') {
                $value = (string)($fieldMappings[$key] ?? '');
            }

            $payload[] = [
                'key' => $key,
                'value' => $value,
            ];
        }

        return [
            'event' => $template['event'],
            'payload' => $payload,
            'templateHandle' => $template['handle'],
            'templateLabel' => $template['label'],
            'enableConditions' => false,
            'conditions' => [
                'applyRule' => 'apply',
                'conditionRule' => 'all',
                'conditions' => [],
            ],
        ];
    }


    // Private Methods
    // =========================================================================

    private function _defineTemplate(array $template): array
    {
        return $this->_normalizeTemplate($template);
    }

    private function _normalizeTemplate(array $template): array
    {
        $payload = [];

        foreach (($template['payload'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $key = trim((string)($row['key'] ?? ''));

            if ($key === '') {
                continue;
            }

            $normalized = [
                'key' => $key,
                'value' => (string)($row['value'] ?? ''),
                'kind' => (string)($row['kind'] ?? 'static'),
            ];

            if ($normalized['kind'] === 'field') {
                $normalized['fieldTypes'] = array_values(array_filter(
                    (array)($row['fieldTypes'] ?? []),
                    fn($type) => is_string($type) && $type !== '',
                ));
                $normalized['mappingLabel'] = (string)($row['mappingLabel'] ?? $key);
                $normalized['required'] = (bool)($row['required'] ?? false);
            }

            $payload[] = $normalized;
        }

        $pageContexts = array_values(array_filter(
            (array)($template['pageContexts'] ?? ['any']),
            fn($context) => is_string($context) && $context !== '',
        ));

        if ($pageContexts === []) {
            $pageContexts = ['any'];
        }

        return [
            'handle' => (string)($template['handle'] ?? ''),
            'label' => (string)($template['label'] ?? ''),
            'description' => (string)($template['description'] ?? ''),
            'category' => (string)($template['category'] ?? 'general'),
            'categoryLabel' => (string)($template['categoryLabel'] ?? Craft::t('formie', 'General')),
            'event' => (string)($template['event'] ?? 'formPageSubmission'),
            'pageContexts' => $pageContexts,
            'payload' => $payload,
        ];
    }
}
