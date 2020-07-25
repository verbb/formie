<?php
namespace verbb\formie\models;

use verbb\formie\Formie;
use verbb\formie\prosemirror\toprosemirror\Renderer as ProseMirrorRenderer;

use Craft;
use craft\base\Model;

class Settings extends Model
{
    // Constants
    // =========================================================================

    const SPAM_BEHAVIOUR_SUCCESS = 'showSuccess';
    const SPAM_BEHAVIOUR_MESSAGE = 'showMessage';

    // Public Properties
    // =========================================================================

    /**
     * The plugin display name.
     *
     * @var string
     */
    public $pluginName = 'Formie';

    /**
     * The slug to the default page when opening the plugin.
     *
     * @var string
     */
    public $defaultPage = 'forms';

    /**
     * The maximum age of an incomplete submission in days
     * before it is deleted in garbage collection.
     *
     * Set to 0 to disable automatic deletion.
     *
     * @var int days
     */
    public $maxIncompleteSubmissionAge = 30;

    public $saveSpam = false;
    public $spamLimit = 500;
    public $spamBehaviour = self::SPAM_BEHAVIOUR_SUCCESS;
    public $spamKeywords = '';
    public $spamBehaviourMessage = '';


    // Public Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    protected function defineRules(): array
    {
        $rules = parent::defineRules();

        $rules[] = [['pluginName', 'defaultPage', 'maxIncompleteSubmissionAge'], 'required'];
        $rules[] = [['pluginName'], 'string', 'max' => 52];
        $rules[] = [['maxIncompleteSubmissionAge'], 'number', 'integerOnly' => true];

        return $rules;
    }
}
