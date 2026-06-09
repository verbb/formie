<?php
namespace verbb\formie\options;

use verbb\formie\elements\Form;
use verbb\formie\elements\Submission;

use craft\base\ElementInterface;

class OptionSourceContext
{
    // Constants
    // =========================================================================

    public const SCOPE_BUILDER = 'builder';
    public const SCOPE_RENDER = 'render';
    public const SCOPE_VALIDATE = 'validate';
    public const SCOPE_DETACH = 'detach';


    // Public Methods
    // =========================================================================

    public function __construct(
        public ?Form $form = null,
        public ?Submission $submission = null,
        public ?ElementInterface $ownerElement = null,
        public ?int $siteId = null,
        public string $scope = self::SCOPE_RENDER,
    ) {
    }
}
