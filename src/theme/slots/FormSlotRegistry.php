<?php
namespace verbb\formie\theme\slots;

use verbb\formie\Formie;
use verbb\formie\helpers\Html;
use verbb\formie\models\Settings;
use verbb\formie\helpers\SetPageReturnUrlHelper;
use verbb\formie\models\ClientModule;
use verbb\formie\models\FieldLayoutPage;
use verbb\formie\models\FieldLayoutRow;
use verbb\formie\models\SlotTag;
use verbb\formie\theme\context\RenderContext;

use Craft;
use craft\helpers\Json;
use craft\helpers\UrlHelper;

use yii\base\Component;

class FormSlotRegistry extends Component
{
    // Public Methods
    // =========================================================================

    public function resolve(string $key, RenderContext $context): ?SlotTag
    {
        return match ($key) {
            'form' => $this->_form($context),
            'formHeader' => $this->_formHeader($context),
            'formMessagesTop' => $this->_formMessagesTop($context),
            'formNavigation' => $this->_formNavigation($context),
            'formBody' => $this->_formBody($context),
            'formFooter' => $this->_formFooter($context),
            'formMessagesBottom' => $this->_formMessagesBottom($context),
            'pages' => $this->_pages($context),
            'messageError' => $this->_messageError($context),
            'messageSuccess' => $this->_messageSuccess($context),
            'formTitle' => $this->_formTitle($context),
            'pageTabs' => $this->_pageTabs($context),
            'pageTab' => $this->_pageTab($context),
            'pageTabLink' => $this->_pageTabLink($context),
            'page' => $this->_page($context),
            'pageContainer' => $this->_pageContainer($context),
            'pageHeader' => $this->_pageHeader($context),
            'pageBody' => $this->_pageBody($context),
            'pageFooter' => $this->_pageFooter($context),
            'pageCaptchas' => $this->_pageCaptchas($context),
            'pageButtons' => $this->_pageButtons($context),
            'pageTitle' => $this->_pageTitle($context),
            'rows' => $this->_rows($context),
            'row' => $this->_row($context),
            'rowSubmitButton' => $this->_rowSubmitButton($context),
            'captchaContainer' => $this->_captchaContainer($context),
            'buttonContainer' => $this->_buttonContainer($context),
            'submitButton' => $this->_submitButton($context),
            'saveButton' => $this->_saveButton($context),
            'backButton' => $this->_backButton($context),
            'progressWrapper' => $this->_progressWrapper($context),
            'progress' => $this->_progress($context),
            'progressContainer' => $this->_progressContainer($context),
            'progressValue' => $this->_progressValue($context),
            'errors' => $this->_errors($context),
            'error' => $this->_error($context),
            default => null,
        };
    }


    // Private Methods
    // =========================================================================

    private function _form(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $moduleManifest = $form ? Formie::$plugin->getClientModuleManifestBuilder()->buildCanonical($form, ClientModule::RENDER_TARGET_FRONTEND) : [];
        $themeClassMap = $form ? $form->getFrontendThemeClassMap() : [];
        $renderFrame = Formie::$plugin->getRendering()->getActiveRenderFrame();
        $renderOptions = $renderFrame?->getRenderOptions() ?? [];
        $renderThemeConfig = $renderOptions['themeConfig'] ?? null;
        $renderTheme = $renderOptions['theme'] ?? null;
        $settings = Formie::$plugin->getSettings();
        $hasStaticCache = $settings->hasStaticCache();
        $errorAriaLive = $settings->errorAriaLive;
        $pendingClientEvents = false;

        if ($form) {
            $flashEvents = Formie::$plugin->getService()->getFlash($form->getFlashNamespace(), 'clientEvents');

            if (is_array($flashEvents) && $flashEvents !== []) {
                $pendingClientEvents = Json::encode($flashEvents);
            }
        }

        return SlotTag::make('form')
            ->core([
                'id' => $form?->getRenderId(),
                'method' => 'post',
                'enctype' => 'multipart/form-data',
                'accept-charset' => 'utf-8',
                'data-formie' => true,
                'data-formie-form' => true,
                // Let JS resolve CSRF by Craft's configured token name (not a hard-coded CRAFT_CSRF_TOKEN).
                'data-formie-csrf-param' => Craft::$app->getRequest()->csrfParam,
                'data-formie-init' => $context->renderOption('initJs', true) ? false : 'false',
                'data-formie-handle' => $form?->handle,
                'data-formie-static-cache' => $hasStaticCache ? true : false,
                'data-formie-submit-method' => $form?->settings->submitMethod,
                'data-formie-submit-action' => $form?->settings->submitAction,
                'data-formie-submit-action-form-hide' => $form?->settings->submitActionFormHide ? true : false,
                'data-formie-automatic-submission-state' => $form?->settings->automaticSubmissionState ? true : false,
                'data-formie-submit-action-message-timeout' => $form?->settings->submitActionMessageTimeout,
                'data-formie-submit-action-message-position' => $form?->settings->submitActionMessagePosition,
                'data-formie-error-message' => $form?->getFrontendErrorMessage(),
                'data-formie-error-message-position' => $form?->settings->errorMessagePosition,
                'data-formie-loading-indicator' => $form?->settings->loadingIndicator,
                'data-formie-loading-indicator-text' => $form?->settings->loadingIndicatorText,
                'data-formie-progress-calculation' => $form?->settings->progressCalculation,
                'data-formie-unload-warning' => $settings->enableUnloadWarning ? true : false,
                'data-formie-error-aria-live' => $errorAriaLive,
                'data-formie-validation-on-focus' => $form?->settings->validationOnFocus ? true : false,
                'data-formie-validation-on-submit' => $form?->settings->validationOnSubmit ? true : false,
                'data-formie-disable-submit-until-valid' => $form?->settings->disableSubmitButtonUntilValid ? true : false,
                'data-formie-scroll-to-top' => $form?->settings->scrollToTop ? true : false,
                'data-formie-clear-submission-endpoint' => UrlHelper::actionUrl('formie/server/submissions/clear-submission'),
                'data-formie-modules' => $moduleManifest ? Json::encode($moduleManifest) : false,
                'data-formie-theme' => $themeClassMap ? Json::encode($themeClassMap) : false,
                'data-formie-theme-config' => (is_array($renderThemeConfig) && $renderThemeConfig !== []) ? Json::encode($renderThemeConfig) : false,
                'data-formie-frontend-theme' => (is_string($renderTheme) && $renderTheme !== '' && $renderTheme !== 'formie') ? $renderTheme : false,
                'data-formie-pending-client-events' => $pendingClientEvents,
            ])
            ->theme([
                'class' => [
                    'formie-form',
                ],
            ]);
    }

    private function _pages(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-pages' => true,
            ])
            ->theme([
                'class' => [
                    'formie-pages',
                ],
            ]);
    }

    private function _pageClientEventJson(?FieldLayoutPage $page): string|false
    {
        if (!$page) {
            return false;
        }

        $settings = $page->getPageSettings();

        if (!$settings || !$settings->enableClientEvents) {
            return false;
        }

        $rows = is_array($settings->clientEventFields) ? $settings->clientEventFields : [];
        $events = \verbb\formie\helpers\ClientEventsHelper::normalizeStoredEvents($settings);

        if ($events !== []) {
            $firstEvent = $events[0];
            $fields = [
                ['label' => 'event', 'value' => (string)($firstEvent['event'] ?? '')],
            ];

            foreach ($firstEvent['payload'] ?? [] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $fields[] = [
                    'label' => (string)($row['key'] ?? ''),
                    'value' => (string)($row['value'] ?? ''),
                ];
            }

            return Json::encode(['fields' => $fields]);
        }

        $fields = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $fields[] = [
                'label' => (string)($row['label'] ?? ''),
                'value' => (string)($row['value'] ?? ''),
            ];
        }

        return Json::encode(['fields' => $fields]);
    }

    private function _formHeader(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-form-header' => true,
            ])
            ->theme([
                'class' => [
                    'formie-form-header',
                ],
            ]);
    }

    private function _formBody(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-form-body' => true,
            ])
            ->theme([
                'class' => [
                    'formie-form-body',
                ],
            ]);
    }

    private function _formMessagesTop(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-form-messages-top' => true,
            ])
            ->theme([
                'class' => [
                    'formie-form-messages',
                ],
            ]);
    }

    private function _formNavigation(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-form-navigation' => true,
            ])
            ->theme([
                'class' => [
                    'formie-form-navigation',
                ],
            ]);
    }

    private function _formFooter(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-form-footer' => true,
            ])
            ->theme([
                'class' => [
                    'formie-form-footer',
                ],
            ]);
    }

    private function _formMessagesBottom(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-form-messages-bottom' => true,
            ])
            ->theme([
                'class' => [
                    'formie-form-messages',
                ],
            ]);
    }

    private function _messageError(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'role' => 'alert',
                'data-formie-message' => true,
                'data-formie-message-error' => true,
            ])
            ->theme([
                'class' => [
                    'formie-message',
                    'formie-message-error',
                ],
            ]);
    }

    private function _messageSuccess(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'role' => 'alert',
                'data-formie-message' => true,
                'data-formie-message-success' => true,
            ])
            ->theme([
                'class' => [
                    'formie-message',
                    'formie-message-success',
                ],
            ]);
    }

    private function _formTitle(RenderContext $context): SlotTag
    {
        return SlotTag::make('h2')
            ->core([
                'data-formie-form-title' => true,
            ])
            ->theme([
                'class' => [
                    'formie-form-title',
                ],
            ]);
    }

    private function _pageTabs(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-page-tabs' => true,
            ])
            ->theme([
                'class' => [
                    'formie-page-tabs',
                ],
            ]);
    }

    private function _pageTab(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage;

        return SlotTag::make('div')
            ->core([
                'data-formie-tab' => true,
                'data-formie-page-id' => $page?->id,
                'data-formie-page-index' => ($page && $form) ? $form->getPageIndex($page) : false,
                'data-formie-conditions' => $page?->getConditionsJson(),
                'data-formie-tab-error' => $context->pageHasErrors() ? true : false,
                'data-formie-tab-complete' => $context->pageIsComplete() ? true : false,
                'aria-current' => $context->pageIsCurrent() ? 'page' : false,
            ])
            ->theme([
                'class' => [
                    'formie-tab',
                    $context->pageHasErrors() ? 'formie-tab-error' : false,
                    $context->pageIsComplete() ? 'formie-tab-complete' : false,
                    $context->pageIsCurrent() ? 'formie-tab-current' : false,
                ],
            ]);
    }

    private function _pageTabLink(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage;

        $params = [
            'handle' => $form?->handle,
            'pageId' => $page?->id,
        ];

        if ($form?->getDraftContext()) {
            $params['draftContextToken'] = $form->getDraftContextToken();
        }

        if ($form?->getRenderId()) {
            $params['renderId'] = $form->getRenderId();
        }

        $returnToken = SetPageReturnUrlHelper::createTokenFromCurrentRequest();

        if ($returnToken !== null) {
            $params[SetPageReturnUrlHelper::QUERY_PARAM] = $returnToken;
        }

        $linkClasses = ['formie-tab-link'];

        if ($form) {
            $themeClassMap = $form->getFrontendThemeClassMap();
            $isCurrent = $context->pageIsCurrent();

            if ($isCurrent && !empty($themeClassMap['tabLinkCurrent'])) {
                $linkClasses = array_merge($linkClasses, $themeClassMap['tabLinkCurrent']);
            } elseif (!$isCurrent && !empty($themeClassMap['tabLinkInactive'])) {
                $linkClasses = array_merge($linkClasses, $themeClassMap['tabLinkInactive']);
            }
        }

        return SlotTag::make('a')
            ->core([
                'href' => UrlHelper::actionUrl('formie/server/submissions/set-page', $params),
                'data-formie-tab-link' => true,
                'data-formie-page-id' => $page?->id,
                'data-formie-page-index' => ($page && $form) ? $form->getPageIndex($page) : false,
            ])
            ->theme([
                'class' => $linkClasses,
            ]);
    }

    private function _page(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage;

        return SlotTag::make('section')
            ->core([
                'id' => ($page && $form) ? "{$form->getRenderId()}-p-{$page->id}" : null,
                'data-formie-page' => true,
                'data-formie-page-id' => $page?->id,
                'data-formie-conditions' => $page?->getConditionsJson(),
                'data-formie-client-event' => $this->_pageClientEventJson($page),
                'data-formie-page-hidden' => $context->isMultipage() && !$context->pageIsCurrent() ? true : false,
            ])
            ->theme([
                'class' => [
                    'formie-page',
                    $context->isMultipage() && !$context->pageIsCurrent() ? 'formie-page-hidden' : false,
                ],
            ]);
    }

    private function _pageContainer(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $tag = $form?->settings->displayCurrentPageTitle ? 'fieldset' : 'div';

        return SlotTag::make($tag)
            ->core([
                'data-formie-page-container' => true,
            ])
            ->theme([
                'class' => [
                    'formie-page-container',
                ],
            ]);
    }

    private function _pageHeader(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-page-header' => true,
            ])
            ->theme([
                'class' => [
                    'formie-page-header',
                ],
            ]);
    }

    private function _pageBody(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-page-body' => true,
            ])
            ->theme([
                'class' => [
                    'formie-page-body',
                ],
            ]);
    }

    private function _pageFooter(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-page-footer' => true,
            ])
            ->theme([
                'class' => [
                    'formie-page-footer',
                ],
            ]);
    }

    private function _pageCaptchas(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-page-captchas' => true,
            ])
            ->theme([
                'class' => [
                    'formie-page-captchas',
                ],
            ]);
    }

    private function _pageButtons(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage ?? $form?->getCurrentPage();
        $pageSettings = $page?->getPageSettings();
        $buttonsPosition = $pageSettings?->buttonsPosition ?? 'left';
        $saveButtonStyle = $pageSettings?->saveButtonStyle ?? 'link';

        return SlotTag::make('div')
            ->core([
                'data-formie-page-buttons' => true,
                'data-formie-buttons-position' => $buttonsPosition,
                'data-formie-save-button-style' => $saveButtonStyle,
            ])
            ->theme([
                'class' => [
                    'formie-page-buttons',
                ],
            ])
            ->instanceAttributes(Html::mergeAttributes(
                $pageSettings?->getContainerAttributes() ?? [],
                ['class' => [$pageSettings?->cssClasses]]
            ));
    }

    private function _captchaContainer(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-captcha-container' => true,
            ])
            ->theme([
                'class' => [
                    'formie-captcha-container',
                ],
            ]);
    }

    private function _pageTitle(RenderContext $context): SlotTag
    {
        return SlotTag::make('h3')
            ->core([
                'data-formie-page-title' => true,
            ])
            ->theme([
                'class' => [
                    'formie-page-title',
                ],
            ]);
    }

    private function _row(RenderContext $context): SlotTag
    {
        $row = $context->row;
        $form = $context->form;
        $page = $context->targetPage ?? $form?->getCurrentPage();
        $pageSettings = $page?->getPageSettings();
        $fieldCount = $row ? count($row->getFields(false)) : null;
        $isHidden = $row ? $row->getIsHidden() : false;
        $rows = $page ? $page->getRows(false) : [];
        $inlineSubmit = $page instanceof FieldLayoutPage
            && $row instanceof FieldLayoutRow
            && $page->shouldRenderSubmitOnLastRow(count($rows) > 0)
            && $page->isLastRow($row);

        if ($inlineSubmit && $fieldCount !== null) {
            $fieldCount++;
        }

        $core = [
            'data-formie-row' => true,
            'data-formie-field-count' => $fieldCount ?: null,
            'data-formie-row-hidden' => $isHidden ? true : false,
        ];

        if ($inlineSubmit) {
            $core['data-formie-row-submit-inline'] = true;
        }

        return SlotTag::make('div')
            ->core($core)
            ->theme([
                'class' => [
                    'formie-row',
                    $isHidden ? 'formie-row-hidden' : false,
                ],
            ]);
    }

    private function _rowSubmitButton(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-row-submit' => true,
            ])
            ->theme([
                'class' => [
                    'formie-row-submit',
                ],
            ]);
    }

    private function _rows(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-rows' => true,
            ])
            ->theme([
                'class' => [
                    'formie-rows',
                ],
            ]);
    }

    private function _buttonContainer(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage ?? $form?->getCurrentPage();
        $pageSettings = $page?->getPageSettings();

        return SlotTag::make('div')
            ->core([
                'data-formie-button-container' => true,
                'data-formie-buttons-position' => $pageSettings?->buttonsPosition ?? 'left',
            ])
            ->theme([
                'class' => [
                    'formie-button-container',
                ],
            ]);
    }

    private function _submitButton(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage ?? $form?->getCurrentPage();

        return SlotTag::make('button')
            ->core([
                'type' => 'submit',
                'name' => 'submitAction',
                'value' => 'submit',
                'data-formie-action' => 'submit',
                'data-formie-conditions' => $page?->getSubmitButtonConditionsJson(),
            ])
            ->theme([
                'class' => [
                    'formie-button',
                    'formie-button-submit',
                    'formie-button-primary',
                ],
            ])
            ->instanceAttributes($page?->getPageSettings()?->getInputAttributes() ?? []);
    }

    private function _saveButton(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage ?? $form?->getCurrentPage();
        $pageSettings = $page?->getPageSettings();

        return SlotTag::make('button')
            ->core([
                'type' => 'submit',
                'name' => 'submitAction',
                'value' => 'save',
                'data-formie-action' => 'save',
                'data-formie-button-style' => $pageSettings?->saveButtonStyle ?? 'link',
            ])
            ->theme([
                'class' => [
                    'formie-button',
                    'formie-button-save',
                    $pageSettings?->saveButtonStyle === 'link' ? 'formie-button-ghost' : 'formie-button-secondary',
                ],
            ])
            ->instanceAttributes($pageSettings?->getInputAttributes() ?? []);
    }

    private function _backButton(RenderContext $context): SlotTag
    {
        $form = $context->form;
        $page = $context->targetPage ?? $form?->getCurrentPage();

        return SlotTag::make('button')
            ->core([
                'type' => 'submit',
                'name' => 'submitAction',
                'value' => 'back',
                'data-formie-action' => 'back',
            ])
            ->theme([
                'class' => [
                    'formie-button',
                    'formie-button-back',
                ],
            ])
            ->instanceAttributes($page?->getPageSettings()?->getInputAttributes() ?? []);
    }

    private function _progressWrapper(RenderContext $context): SlotTag
    {
        $form = $context->form;

        return SlotTag::make('div')
            ->core([
                'data-formie-progress-wrapper' => true,
                'data-formie-progress-position' => $form?->settings->progressPosition,
            ])
            ->theme([
                'class' => [
                    'formie-progress-wrapper',
                ],
            ]);
    }

    private function _progress(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-progress' => true,
            ])
            ->theme([
                'class' => [
                    'formie-progress',
                ],
            ]);
    }

    private function _progressContainer(RenderContext $context): SlotTag
    {
        $progress = (float)$context->get('progress', 0);
        $state = 'middle';

        if ($progress <= 0) {
            $state = 'start';
        } else if ($progress >= 100) {
            $state = 'end';
        }

        return SlotTag::make('div')
            ->core([
                'style' => [
                    '--formie-progress' => "{$progress}%",
                    'width' => "{$progress}%",
                ],
                'role' => 'progressbar',
                'data-formie-progress-bar' => true,
                'data-formie-progress-state' => $state,
                'aria' => [
                    'valuenow' => $progress,
                    'valuemin' => 0,
                    'valuemax' => 100,
                ],
            ])
            ->theme([
                'class' => [
                    'formie-progress-bar',
                ],
            ]);
    }

    private function _progressValue(RenderContext $context): SlotTag
    {
        return SlotTag::make('span')
            ->core([
                'data-formie-progress-value' => true,
            ])
            ->theme([
                'class' => [
                    'formie-progress-value',
                ],
            ]);
    }

    private function _errors(RenderContext $context): SlotTag
    {
        $errorAriaLive = Formie::$plugin->getSettings()->errorAriaLive;
        $core = [
            'data-formie-errors' => true,
        ];

        if ($errorAriaLive !== Settings::ERROR_ARIA_LIVE_OFF) {
            $core['aria-live'] = $errorAriaLive;
            $core['aria-atomic'] = true;
        }

        return SlotTag::make('div')
            ->core($core)
            ->theme([
                'class' => [
                    'formie-errors',
                ],
            ]);
    }

    private function _error(RenderContext $context): SlotTag
    {
        return SlotTag::make('div')
            ->core([
                'data-formie-error' => true,
                'role' => 'alert',
            ])
            ->theme([
                'class' => [
                    'formie-error',
                ],
            ]);
    }
}
