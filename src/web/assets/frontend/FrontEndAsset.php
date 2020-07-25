<?php
namespace verbb\formie\web\assets\frontend;

use craft\helpers\Json;
use craft\web\AssetBundle;
use craft\web\View;
use verbb\formie\elements\Form;

class FrontEndAsset extends AssetBundle
{
    // Public Properties
    // =========================================================================

    /**
     * @inheritDoc
     */
    public $sourcePath = '@verbb/formie/web/assets/frontend/dist';

    /**
     * @var Form
     */
    public $form;


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public function registerAssetFiles($view)
    {
        $this->js = [];
        $this->css = [];

        $template = $this->form->getTemplate();

        // Output everything by default, unless there's a template setup
        $outputCssLayout = true;
        $outputCssTheme = true;
        $outputJs = true;

        if ($template) {
            $outputCssLayout = $template->outputCssLayout;
            $outputCssTheme = $template->outputCssTheme;
            $outputJs = $template->outputJs;
        }

        // Only output this if we're not showing the theme. We bundle the two together
        // during build, so we don't have to serve two stylesheets.
        if ($outputCssLayout && !$outputCssTheme) {
            $this->css[] = 'css/formie-base.css';
        }

        if ($outputCssLayout && $outputCssTheme) {
            $this->css[] = 'css/formie-theme.css';
        }

        // TODO: maybe refactor this so there's less mucking around
        $settings = $this->form->settings->toArray();
        $settings['redirectEntry'] = $this->form->getRedirectEntry()->url ?? '';
        $settings['currentPageId'] = $this->form->getCurrentPage()->id ?? '';
        $settings['submitActionMessage'] = $this->form->settings->getSubmitActionMessage() ?? '';

        if ($outputJs) {
            $this->js[] = 'js/formie-form.js';

            $variables = Json::encode([
                'formId' => $this->form->id,
                'settings' => $settings,
            ]);

            $view->registerJs('new FormieForm(' . $variables . ');', View::POS_END);
        }

        parent::registerAssetFiles($view);
    }
}
