<?php
namespace verbb\formie\elements\db;

use verbb\formie\base\NestedFieldInterface;
use verbb\formie\base\NestedFieldTrait;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;

use yii\base\Model;

class NestedFieldRowQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $fieldId = null;
    public mixed $ownerId = null;

    protected array $defaultOrderBy = ['formie_nestedfieldrows.sortOrder' => SORT_ASC];

    private array $_blocks = [];


    // Public Methods
    // =========================================================================

    /**
     * Narrows the query results based on the field the nested field rows belong to.
     *
     * @param NestedFieldInterface $value The property value
     * @return static self reference
     * @uses $fieldId
     */
    public function field(NestedFieldInterface $value): static
    {
        $this->fieldId = $value->id;
        return $this;
    }

    /**
     * Sets the [[fieldId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     */
    public function fieldId(array|int|null $value): static
    {
        $this->fieldId = $value;
        return $this;
    }

    /**
     * Sets the [[ownerId]] property.
     *
     * @param int|int[]|null $value The property value
     * @return static self reference
     */
    public function ownerId(array|int|null $value): static
    {
        $this->ownerId = $value;
        return $this;
    }

    /**
     * Sets the [[ownerId]] and [[ownerSiteId]] properties based on a given element.
     *
     * @param ElementInterface $owner The owner element
     * @return static self reference
     */
    public function owner(ElementInterface $owner): static
    {
        /** @var Element $owner */
        $this->ownerId = $owner->id;
        $this->siteId = $owner->siteId;

        return $this;
    }

    public function setBlocks($blocks): void
    {
        $this->_blocks = $blocks;
    }

    /**
     * @inheritdoc
     */
    public function all($db = null): array
    {
        if ($this->_blocks) {
            // Override the default `.all()` behaviour to return any pre-defined blocks instead of querying the db.
            return $this->_blocks;
        }

        return parent::all($db);
    }

    /**
     * @inheritdoc
     */
    public function one($db = null): Model|array|null
    {
        if ($this->_blocks) {
            return reset($this->_blocks) ?: null;
        }

        return parent::one($db);
    }


    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        if ($this->fieldId !== null && empty($this->fieldId)) {
            throw new QueryAbortedException();
        }

        $this->joinElementTable('formie_nestedfieldrows');

        // Figure out which content table to use
        $this->contentTable = null;

        if (!$this->fieldId && $this->id) {
            $fieldIds = (new Query())
                ->select(['fieldId'])
                ->distinct()
                ->from(['{{%formie_nestedfieldrows}}'])
                ->where(Db::parseParam('id', $this->id))
                ->column();

            $this->fieldId = count($fieldIds) === 1 ? $fieldIds[0] : $fieldIds;
        }

        if ($this->fieldId && is_numeric($this->fieldId)) {
            /** @var NestedFieldInterface|NestedFieldTrait $nestedField */
            $nestedField = Craft::$app->getFields()->getFieldById($this->fieldId);

            if ($nestedField) {
                $this->contentTable = $nestedField->contentTable;
            }
        }

        $this->query->select([
            'formie_nestedfieldrows.fieldId',
            'formie_nestedfieldrows.ownerId',
            'formie_nestedfieldrows.sortOrder',
        ]);

        if ($this->fieldId) {
            $this->subQuery->andWhere(Db::parseParam('formie_nestedfieldrows.fieldId', $this->fieldId));
        }

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('formie_nestedfieldrows.ownerId', $this->ownerId));
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function customFields(): array
    {
        // This method won't get called if $this->fieldId isn't set to a single int
        /** @var NestedFieldInterface|NestedFieldTrait $nestedField */
        $nestedField = Craft::$app->getFields()->getFieldById($this->fieldId);
        return $nestedField->getCustomFields();
    }
}
