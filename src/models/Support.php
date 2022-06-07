<?php
namespace verbb\formie\models;

use Craft;
use craft\base\Model;

class Support extends Model
{
    // Properties
    // =========================================================================

    public $fromEmail;
    public $formId;
    public $message;
    public $attachments = [];


    // Public Methods
    // =========================================================================

    public function attributeLabels()
    {
        return [
            'fromEmail' => Craft::t('formie', 'Your Email'),
            'formId' => Craft::t('formie', 'Your Form'),
        ];
    }

    public function rules()
    {
        return [
            [['fromEmail', 'formId', 'message'], 'required'],
            [['fromEmail'], 'email'],
            [['fromEmail'], 'string', 'min' => 5, 'max' => 255],
        ];
    }

}
