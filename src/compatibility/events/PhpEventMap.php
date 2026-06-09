<?php
namespace verbb\formie\compatibility\events;

use verbb\formie\Formie;
use verbb\formie\events\RegisterPredefinedOptionsEvent;
use verbb\formie\events\SendNotificationEvent;
use verbb\formie\events\TriggerIntegrationEvent;
use verbb\formie\services\Integrations;
use verbb\formie\services\Notifications;
use verbb\formie\services\OptionSources;
use verbb\formie\services\Submissions;

use Craft;

use yii\base\Event;

class PhpEventMap
{
    // Properties
    // =========================================================================

    private static bool $registered = false;

    private const LEGACY_PREDEFINED_OPTIONS_CLASS = 'verbb\\formie\\services\\PredefinedOptions';


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

        Event::on(OptionSources::class, OptionSources::EVENT_REGISTER_PREDEFINED_OPTIONS, static function(RegisterPredefinedOptionsEvent $event) {
            if (!Event::hasHandlers(self::LEGACY_PREDEFINED_OPTIONS_CLASS, OptionSources::EVENT_REGISTER_PREDEFINED_OPTIONS)) {
                return;
            }

            Craft::$app->getDeprecator()->log(
                self::LEGACY_PREDEFINED_OPTIONS_CLASS . '::' . OptionSources::EVENT_REGISTER_PREDEFINED_OPTIONS,
                'Registering predefined option handlers on `PredefinedOptions` has been deprecated. Register handlers on `OptionSources` instead.'
            );

            self::_triggerLegacyClassEvent(self::LEGACY_PREDEFINED_OPTIONS_CLASS, OptionSources::EVENT_REGISTER_PREDEFINED_OPTIONS, $event);
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

    private static function _triggerLegacyClassEvent(string $class, string $eventName, Event $event): void
    {
        $originalName = $event->name ?? null;
        $originalSender = $event->sender ?? null;

        Event::trigger($class, $eventName, $event);

        $event->name = $originalName;
        $event->sender = $originalSender;
    }
}
