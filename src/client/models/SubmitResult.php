<?php
namespace verbb\formie\client\models;

use verbb\formie\client\BaseClientModel;

class SubmitResult extends BaseClientModel
{
    // Properties
    // =========================================================================

    public bool $success = false;
    public ?string $submissionUid = null;
    public ?string $currentPageId = null;
    public ?string $nextPageId = null;
    public ?string $previousPageId = null;
    public bool $isFinalPage = false;

    public array $errors = [
        'form' => [],
        'fields' => [],
        'pages' => [],
    ];

    public array $messages = [
        'notice' => null,
        'error' => null,
    ];

    public ?FormSession $session = null;
    public ?array $quizResult = null;
    public array $clientEvents = [];


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        parent::__construct($config);
    }
}
