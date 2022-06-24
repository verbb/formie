<?php
namespace verbb\formie\integrations\feedme\elements;

use verbb\formie\Formie;
use verbb\formie\elements\Submission as SubmissionElement;

use Craft;

use craft\feedme\Plugin as FeedMe;
use craft\feedme\base\Element;

use Cake\Utility\Hash;

class Submission extends Element
{
    // Properties
    // =========================================================================

    public static $name = 'Submission';
    public static $class = 'verbb\formie\elements\Submission';

    public $element;


    // Templates
    // =========================================================================

    public function getGroupsTemplate()
    {
        return 'formie/integrations/feedme/elements/groups';
    }

    public function getColumnTemplate()
    {
        return 'formie/integrations/feedme/elements/column';
    }

    public function getMappingTemplate()
    {
        return 'formie/integrations/feedme/elements/map';
    }


    // Public Methods
    // =========================================================================

    public function getGroups()
    {
        if (Formie::getInstance()) {
            return Formie::$plugin->getForms()->getAllForms();
        }
    }

    public function getQuery($settings, $params = [])
    {
        $query = SubmissionElement::find()
            ->anyStatus()
            ->formId($settings['elementGroup'][SubmissionElement::class])
            ->siteId(Hash::get($settings, 'siteId') ?: Craft::$app->getSites()->getPrimarySite()->id);

        Craft::configure($query, $params);

        return $query;
    }

    public function setModel($settings)
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

    protected function parseStatusId($feedData, $fieldInfo)
    {
        $value = $this->fetchSimpleValue($feedData, $fieldInfo);

        return Formie::$plugin->getStatuses()->getStatusByHandle($value)->id;
    }

}
