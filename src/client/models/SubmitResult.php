<?php
namespace verbb\formie\client\models;

use verbb\formie\client\BaseClientModel;

class SubmitResult extends BaseClientModel
{
    // Static Methods
    // =========================================================================

    public static function rejection(int $status): self
    {
        $message = $status === 429 ? \Craft::t('formie', 'Please retry shortly.') : \Craft::t('formie', 'Unable to perform the action.');
        return new self(['httpStatus' => $status, 'outcome' => 'rejected', 'errors' => ['form' => [$message], 'fields' => []], 'messages' => ['notice' => null, 'error' => $message]]);
    }


    // Properties
    // =========================================================================

    public bool $success = false;
    public string $outcome = '';
    public ?int $version = null;
    public int $httpStatus = 200;
    public ?string $submissionUid = null;
    public ?string $resumeToken = null;
    public ?string $resumeUrl = null;
    public ?int $resumeTokenExpiresAt = null;
    public ?string $currentPageId = null;
    public ?string $nextPageId = null;
    public ?string $previousPageId = null;
    public bool $isFinalPage = false;

    public array $errors = [
        'form' => [],
        'fields' => [],
    ];

    public array $messages = [
        'notice' => null,
        'error' => null,
    ];

    public ?FormSession $session = null;
    public ?array $quizResult = null;
    public ?array $completion = null;
    public ?array $redirect = null;
    public array $clientEvents = [];
    public ?array $payment = null;


    // Public Methods
    // =========================================================================

    public function __construct($config = [])
    {
        parent::__construct($config);
    }
}
