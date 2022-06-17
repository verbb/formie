<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;
use craft\web\UploadedFile;

class Support extends Model
{
    // Properties
    // =========================================================================

    public ?string $fromEmail = null;
    public ?int $formId = null;
    public ?string $message = null;
    public ?array $attachments = null;


    // Public Methods
    // =========================================================================

    public function attributeLabels(): array
    {
        return [
            'fromEmail' => Craft::t('formie', 'Your Email'),
            'formId' => Craft::t('formie', 'Your Form'),
        ];
    }

    public function rules(): array
    {
        return [
            [['fromEmail', 'formId', 'message'], 'required'],
            [['fromEmail'], 'email'],
            [['fromEmail'], 'string', 'min' => 5, 'max' => 255],
        ];
    }

}
