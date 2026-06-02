<?php
namespace verbb\formie\compatibility\events;

use verbb\formie\Formie;
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\services\Integrations;
use verbb\formie\services\Notifications;
use verbb\formie\services\Submissions;

use yii\base\Event;

class PhpEventMap
{
    // Properties
    // =========================================================================

    private static bool $registered = false;


    // Static Methods
    // =========================================================================

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        self::$registered = true;

        Event::on(Notifications::class, Notifications::EVENT_BEFORE_SEND_NOTIFICATION, static function(SendNotificationEvent $event) {
            $submissions = Formie::$plugin->getSubmissions();

            if (!$submissions->hasEventHandlers(Submissions::EVENT_BEFORE_SEND_NOTIFICATION)) {
                return;
            }

            self::_triggerLegacyOwnerEvent($submissions, Submissions::EVENT_BEFORE_SEND_NOTIFICATION, $event);
        });

        Event::on(Integrations::class, Integrations::EVENT_BEFORE_TRIGGER_INTEGRATION, static function(TriggerIntegrationEvent $event) {
            $submissions = Formie::$plugin->getSubmissions();

            if (!$submissions->hasEventHandlers(Submissions::EVENT_BEFORE_TRIGGER_INTEGRATION)) {
                return;
            }

            self::_triggerLegacyOwnerEvent($submissions, Submissions::EVENT_BEFORE_TRIGGER_INTEGRATION, $event);
        });
    }

    private static function _triggerLegacyOwnerEvent(Submissions $submissions, string $eventName, Event $event): void
    {
        $originalName = $event->name ?? null;
        $originalSender = $event->sender ?? null;

        $submissions->trigger($eventName, $event);

        $event->name = $originalName;
        $event->sender = $originalSender;
    }
}
