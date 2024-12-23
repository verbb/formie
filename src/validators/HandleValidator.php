<?php
namespace verbb\formie\validators;

use Craft;

use yii\validators\Validator;

class HandleValidator extends Validator
{
    // Properties
    // =========================================================================

    public static string $handlePattern = '[a-zA-Z][a-zA-Z0-9_]*';
    
    public array $reservedWords = [];


    // Public Methods
    // =========================================================================

    public function validateAttribute($model, $attribute): void
    {
        $handle = $model->$attribute;

        // Handles are always required, so if it's blank, the required validator will catch this.
        if ($handle) {
            $reservedWords = array_map('strtolower', $this->reservedWords);
            $lcHandle = strtolower($handle);

            if (in_array($lcHandle, $reservedWords, true)) {
                $message = Craft::t('app', '“{handle}” is a reserved word.', ['handle' => $handle]);

                $this->addError($model, $attribute, $message);
            } else {
                if (!preg_match('/^' . static::$handlePattern . '$/', $handle)) {
                    $altMessage = Craft::t('app', '“{handle}” isn’t a valid handle.', ['handle' => $handle]);
                    $message = $this->message ?? $altMessage;
                    
                    $this->addError($model, $attribute, $message);
                }
            }
        }
    }

    public function validateValue($value): ?array
    {
        $reservedWords = array_map('strtolower', $this->reservedWords);
        $lcHandle = strtolower($value);

        if (in_array($lcHandle, $reservedWords, true)) {
            return [Craft::t('app', '“{handle}” is a reserved word.', ['handle' => $value]), []];
        } else {
            if (!preg_match('/^' . static::$handlePattern . '$/', $value)) {
                $altMessage = Craft::t('app', '“{handle}” isn’t a valid handle.', ['handle' => $value]);
                $message = $this->message ?? $altMessage;

                return [$message, []];
            }
        }

        return null;
    }
}
