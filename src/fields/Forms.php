<?php
namespace verbb\formie\fields;

use verbb\formie\elements\Form;
use verbb\formie\elements\db\FormQuery;
use verbb\formie\gql\arguments\FormArguments;
use verbb\formie\gql\interfaces\FormInterface;
use verbb\formie\gql\resolvers\FormResolver;
use verbb\formie\helpers\Table;

use Craft;
use craft\elements\ElementCollection;
use craft\fields\BaseRelationField;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;

use GraphQL\Type\Definition\Type;

class Forms extends BaseRelationField
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return Craft::t('formie', 'Forms (Formie)');
    }

    public static function icon(): string
    {
        return '@verbb/formie/icon-mask.svg';
    }

    public static function elementType(): string
    {
        return Form::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('formie', 'Add a form');
    }

    public static function phpType(): string
    {
        return sprintf('\\%s|\\%s<\\%s>', FormQuery::class, ElementCollection::class, Form::class);
    }


    // Public Methods
    // =========================================================================

    // TODO Override until the next breakpoint
    // https://github.com/verbb/formie/discussions/1696
    public function getSourceOptions(): array
    {
        $options = parent::getSourceOptions();

        foreach ($options as $key => $option) {
            if (isset($option['value'])) {
                $parts = explode(':', $option['value']);

                if (isset($parts[1]) && is_numeric($parts[1])) {
                    $templateUid = Db::uidById(Table::FORMIE_FORM_TEMPLATES, $parts[1]);

                    if ($templateUid) {
                        $options[$key]['value'] = $parts[0] . ':' . $templateUid;
                    }
                }
            }
        }

        ArrayHelper::multisort($options, 'label', SORT_ASC, SORT_NATURAL | SORT_FLAG_CASE);
        
        return $options;
    }

    public function getContentGqlType(): array|Type
    {
        return [
            'name' => $this->handle,
            'type' => Type::listOf(FormInterface::getType()),
            'args' => FormArguments::getArguments(),
            'resolve' => FormResolver::class . '::resolve',
        ];
    }
}
