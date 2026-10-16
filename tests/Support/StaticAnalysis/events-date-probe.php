<?php

// Keep the optional Events facade double out of the main test process.
require dirname(__DIR__, 2) . '/runtime/verify.php';

class EventsDateFacade
{
    public static $plugin;
}
class_alias(EventsDateFacade::class, 'verbb\\events\\Events');
EventsDateFacade::$plugin = new class {
    public function getEventTypes() { return $this; }
    public function getEventTypeById($id) { return (object)['id' => $id]; }
};

$event = new class extends \craft\base\Element {
    public ?DateTime $startDate = null;
    public ?DateTime $endDate = null;
    public ?int $typeId = null;
};
$integration = new class extends \verbb\formie\integrations\elements\EventsEvent {
    public $testEvent;
    public bool $captured = false;
    private int $mappingCall = 0;
    public function run($submission) { return $this->executePayload($submission); }
    protected function getElementForPayload(string $elementType, string $identifier, \verbb\formie\elements\Submission $submission, array $criteria = []): \craft\base\ElementInterface { return $this->testEvent; }
    public function getConfigValue(string $key): mixed { return []; }
    public function getFieldMappingValues(\verbb\formie\elements\Submission $submission, ?array $fieldMapping, mixed $fieldSettings = null) {
        return $this->mappingCall++ === 0 ? [
            'startDate' => new DateTime('2026-10-17 09:30:00', new DateTimeZone('Australia/Melbourne')),
            'endDate' => null,
            'title' => 'Mapped event',
        ] : [];
    }
    public function beforeSendPayload(\verbb\formie\elements\Submission $submission, string &$endpoint, mixed &$payload, string &$method, string $contentType = 'json'): bool {
        $this->captured = true;
        return false;
    }
};
$integration->eventTypeId = 1;
$integration->overwriteValues = true;
$integration->testEvent = $event;
$integration->run(new \verbb\formie\elements\Submission(['siteId' => Craft::$app->getSites()->getPrimarySite()->id]));
echo json_encode([
    'startDate' => $event->startDate?->format(DateTimeInterface::ATOM),
    'timezone' => $event->startDate?->getTimezone()->getName(),
    'endDate' => $event->endDate,
    'title' => $event->title,
    'captured' => $integration->captured,
], JSON_THROW_ON_ERROR) . "\n";
