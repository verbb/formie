<?php
namespace verbb\formie\services;

use Craft;

use craft\helpers\Session;
use yii\base\Component;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    public function setFlash(string $namespace, string $key, mixed $value, bool $removeAfterAccess = true): void
    {
        if (!Session::exists()) {
            return;
        }

        $key = "formie.$namespace:$key";
        Craft::$app->getSession()->setFlash($key, $value, $removeAfterAccess);
    }

    public function getFlash(string $namespace, string $key, mixed $defaultValue = null, bool $delete = false): mixed
    {
        if (!Session::exists()) {
            return $defaultValue;
        }

        $key = "formie.$namespace:$key";
        return Craft::$app->getSession()->getFlash($key, $defaultValue, $delete);
    }

    public function setError(string $namespace, string $message): void
    {
        $this->setFlash($namespace, 'error', $message);
    }

    public function setNotice(string $namespace, string $message): void
    {
        $this->setFlash($namespace, 'notice', $message);
    }

    public function getFieldNamespaceForScript($field): string
    {
        $scriptNamespace = null;
        $currentNamespace = Craft::$app->getView()->getNamespace();

        $namespace = $field->namespace == null ? $field->handle : "{$field->namespace}[{$field->handle}]";

        if ($currentNamespace) {
            $scriptNamespace = Craft::$app->getView()->namespaceInputName($currentNamespace, $field->namespace);
        }

        return Craft::$app->getView()->namespaceInputName($namespace, $scriptNamespace);
    }

    public function onBeforeSavePluginSettings($event): void
    {
        $settings = $event->plugin->getSettings();

        // Reset the theme config if storing in project config
        $settings->themeConfig = [];

        $event->plugin->setSettings($settings->toArray());
    }
}
