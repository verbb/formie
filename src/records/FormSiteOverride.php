<?php
namespace verbb\formie\records;

use verbb\formie\elements\Form;
use verbb\formie\helpers\Table;

use craft\db\ActiveRecord;

use yii\db\ActiveQueryInterface;

class FormSiteOverride extends ActiveRecord
{
    // Static Methods
    // =========================================================================

    public static function tableName(): string
    {
        return Table::FORMIE_FORM_SITE_OVERRIDES;
    }


    // Public Methods
    // =========================================================================

    public function getForm(): ActiveQueryInterface
    {
        return $this->hasOne(Form::class, ['id' => 'formId']);
    }
}
