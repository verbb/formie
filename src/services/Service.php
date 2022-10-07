<?php
namespace verbb\formie\services;

use Craft;

use yii\base\Component;

class Service extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Sets a flash message.
     * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen
     * in the next request.
     * If there is already an existing flash message with the same key, it will be overwritten by the new one.
     *
     * @param string $namespace
     * @param string $key
     * @param mixed $value
     * @param bool $removeAfterAccess
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function setFlash(string $namespace, string $key, mixed $value, bool $removeAfterAccess = true): void
    {
        $key = "formie.$namespace:$key";
        Craft::$app->getSession()->setFlash($key, $value, $removeAfterAccess);
    }

    /**
     * Returns a flash message.
     *
     * @param string $namespace
     * @param string $key
     * @param mixed|null $defaultValue
     * @param bool $delete
     * @return mixed|null
     * @noinspection PhpDocMissingThrowsInspection
     */
    public function getFlash(string $namespace, string $key, mixed $defaultValue = null, bool $delete = false): mixed
    {
        $key = "formie.$namespace:$key";
        return Craft::$app->getSession()->getFlash($key, $defaultValue, $delete);
    }

    /**
     * Stores an error message in the user’s flash data.
     *
     * @param string $namespace
     * @param string $message
     */
    public function setError(string $namespace, string $message): void
    {
        $this->setFlash($namespace, 'error', $message);
    }

    /**
     * Stores a notice message in the user’s flash data.
     *
     * @param string $namespace
     * @param string $message
     */
    public function setNotice(string $namespace, string $message): void
    {
        $this->setFlash($namespace, 'notice', $message);
    }

    /**
     * Checks if a plugin is both installed and enabled.
     *
     * @param string $plugin The plugin handle
     * @return bool Whether the plugin is both installed and enabled
     */
    public function isPluginInstalledAndEnabled(string $plugin): bool
    {
        $pluginsService = Craft::$app->getPlugins();

        // Ensure that we check if initialized, installed and enabled. 
        // The plugin might be installed but disabled, or installed and enabled, but missing plugin files.
        return $pluginsService->isPluginInstalled($plugin) && $pluginsService->isPluginEnabled($plugin) && $pluginsService->getPlugin($plugin);
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
