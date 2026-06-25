<?php
namespace verbb\formie\deprecations;

use verbb\formie\events\ModifyFormHtmlTagEvent;
use verbb\formie\events\ModifyFormSlotTagEvent;

use Craft;

trait FormDeprecations
{
    // Public Methods
    // =========================================================================

    public function getFormId(bool $useCache = true): string
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie forms’ `getFormId()` method has been deprecated. Use `getRenderId()` instead.');

        return $this->getRenderId($useCache);
    }

    public function setFormId(string $value): void
    {
        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(__METHOD__, 'Formie forms’ `setFormId()` method has been deprecated. Use `setRenderId()` instead.');

        $this->setRenderId($value);
    }


    // Protected Methods
    // =========================================================================

    protected function triggerDeprecatedHtmlTagEvent(ModifyFormSlotTagEvent $event): void
    {
        if (!$this->hasEventHandlers(static::EVENT_MODIFY_HTML_TAG)) {
            return;
        }

        // Deprecated in 4.0.0
        Craft::$app->getDeprecator()->log(static::class . '::EVENT_MODIFY_HTML_TAG', 'Form `EVENT_MODIFY_HTML_TAG` has been deprecated. Use `EVENT_MODIFY_SLOT_TAG` instead.');

        $legacyEvent = new ModifyFormHtmlTagEvent([
            'form' => $event->form,
            'tag' => $event->tag,
            'key' => $event->key,
            'context' => $event->context,
        ]);

        $this->trigger(static::EVENT_MODIFY_HTML_TAG, $legacyEvent);
        $event->tag = $legacyEvent->tag;
        $event->context = $legacyEvent->context;
    }
}
