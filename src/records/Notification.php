<?php
namespace verbb\formie\records;

use craft\db\ActiveRecord;

/**
 * Class Notification
 *
 * @property int $id
 * @property int $formId
 * @property int $templateId
 * @property string $name
 * @property bool $enabled
 * @property string $subject
 * @property string $to
 * @property string $cc
 * @property string $bcc
 * @property string $replyTo
 * @property string $replyToName
 * @property string $from
 * @property string $fromName
 * @property string $sender
 * @property string $content
 * @property boolean $attachFiles
 * @property string $uid
 *
 * @package Formie
 */
class Notification extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    /**
     * @inheritDoc
     */
    public static function tableName(): string
    {
        return '{{%formie_notifications}}';
    }
}
