<?php
namespace verbb\formie\integrations\feedme\elements;

use verbb\formie\Formie;
use verbb\formie\elements\Submission as SubmissionElement;

use Craft;
use craft\base\ElementInterface;

use craft\feedme\base\Element;

use Cake\Utility\Hash;

class Submission extends Element
{
    // Properties
    // =========================================================================

    public static string $class = SubmissionElement::class;
    public static string $name = 'Submission';

    public $element = null;


    // Templates
    // =========================================================================

    public function getGroupsTemplate(): string
    {
        return 'formie/integrations/feedme/elements/groups';
    }

    public function getColumnTemplate(): string
    {
        return 'formie/integrations/feedme/elements/column';
    }

    public function getMappingTemplate(): string
    {
        return 'formie/integrations/feedme/elements/map';
    }


    // Public Methods
    // =========================================================================

    public function getGroups(): array
    {
        if (Formie::$plugin) {
            return Formie::$plugin->getForms()->getAllForms();
        }

        return [];
    }

    public function getQuery($settings, array $params = []): mixed
    {
        $query = SubmissionElement::find()
            ->status(null)
            ->formId($settings['elementGroup'][SubmissionElement::class])
            ->siteId(Hash::get($settings, 'siteId') ?: Craft::$app->getSites()->getPrimarySite()->id);

        Craft::configure($query, $params);

        return $query;
    }

    public function setModel($settings): \craft\base\Element
    {
        $this->element = new SubmissionElement();
        $this->element->formId = $settings['elementGroup'][SubmissionElement::class];

        $siteId = Hash::get($settings, 'siteId');

        if ($siteId) {
            $this->element->siteId = $siteId;
        }

        return $this->element;
    }


    // Protected Methods
    // =========================================================================

    protected function parseStatusId($feedData, $fieldInfo): ?int
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);

        return Formie::$plugin->getStatuses()->getStatusByHandle($value)->id;
    }

}
