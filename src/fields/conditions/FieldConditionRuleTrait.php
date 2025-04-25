<?php
namespace verbb\formie\fields\conditions;

use verbb\formie\base\FieldInterface;

use Craft;
use craft\base\ElementInterface;
use craft\errors\InvalidFieldException;

use yii\base\InvalidConfigException;
use yii\db\QueryInterface;

trait FieldConditionRuleTrait
{
    // Properties
    // =========================================================================

    private string $_fieldUid = '';


    // Public Methods
    // =========================================================================

    public function getGroupLabel(): ?string
    {
        return Craft::t('app', 'Fields');
    }

    public function setFieldUid(string $uid): void
    {
        $this->_fieldUid = $uid;
    }

    public function fields(): array
    {
        return $this->getCondition()->getFields();
    }

    public function field(): ?FieldInterface
    {
        foreach ($this->fields() as $field) {
            if ($field->uid === $this->_fieldUid) {
                return $field;
            }
        }

        return null;
    }

    public function getConfig(): array
    {
        return array_merge(parent::getConfig(), array_filter([
            'fieldUid' => $this->_fieldUid,
        ]));
    }

    public function getLabel(): string
    {
        return $this->field()->label;
    }

    public function getLabelHint(): ?string
    {
        static $showHandles = null;

        $showHandles ??= Craft::$app->getUser()->getIdentity()?->getPreference('showFieldHandles') ?? false;

        return $showHandles ? $this->field()->handle : null;
    }

    public function getExclusiveQueryParams(): array
    {
        try {
            $field = $this->field();
        } catch (InvalidConfigException) {
            // The field doesn't exist
            return [];
        }

        return [$field->handle];
    }

    public function modifyQuery(QueryInterface $query): void
    {
        $value = $this->elementQueryParam();

        if ($value !== null) {
            if ($field = $this->field()) {
                $params = [];
                $condition = $field::queryCondition([$field], $value, $params);

                if ($condition === false) {
                    /** @phpstan-ignore-next-line */
                    $query->andWhere('0=1');
                } elseif ($condition !== null) {
                    /** @phpstan-ignore-next-line */
                    $query->andWhere($condition, $params);
                }
            }
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        try {
            $field = $this->field();
        } catch (InvalidConfigException) {
            // The field doesn't exist
            return true;
        }

        try {
            $value = $element->getFieldValue($field->handle);
        } catch (InvalidFieldException) {
            // The field doesn't belong to the element's field layout
            return false;
        }

        return $this->matchFieldValue($value);
    }


    // Protected Methods
    // =========================================================================

    abstract protected function elementQueryParam(): mixed;

    abstract protected function matchFieldValue($value): bool;

    protected function defineRules(): array
    {
        return array_merge(parent::defineRules(), [
            [['fieldUid'], 'safe'],
        ]);
    }
}
