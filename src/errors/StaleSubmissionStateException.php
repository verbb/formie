<?php
namespace verbb\formie\errors;

use verbb\formie\elements\Form;

use yii\base\Exception;

class StaleSubmissionStateException extends Exception
{
    // Properties
    // =========================================================================

    public Form $form;
    public string $source;
    public string $value;


    // Public Methods
    // =========================================================================

    public function __construct(Form $form, string $source, string $value, ?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        $this->form = $form;
        $this->source = $source;
        $this->value = $value;

        parent::__construct($message ?: 'Stale submission continuity state.', $code, $previous);
    }
}
