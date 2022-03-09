<?php
namespace verbb\formie\gql\types;

use verbb\formie\gql\interfaces\SubmissionInterface;

use craft\gql\types\elements\Element;

class SubmissionType extends Element
{
    // Public Methods
    // =========================================================================

    public function __construct(array $config)
    {
        $config['interfaces'] = [
            SubmissionInterface::getType(),
        ];

        parent::__construct($config);
    }
}
