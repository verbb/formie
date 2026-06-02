<?php
namespace verbb\formie\compatibility\client;

use verbb\formie\Formie;
use verbb\formie\client\bootstrap\FormBootstrapBuilder;
use verbb\formie\client\bootstrap\FormDefinitionBuilder;
use verbb\formie\client\ClientSessionService;
use verbb\formie\client\modules\ClientModuleManifestBuilder;
use verbb\formie\server\ServerRenderPayloadBuilder;

use Craft;

class ClientCompatibility
{
    public function __construct(private Formie $plugin)
    {
    }

    public function getSessionService(): ClientSessionService
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Client compatibility `getSessionService()` has been deprecated. Use `getClientSessionService()` instead.');

        return $this->plugin->getClientSessionService();
    }

    public function getFormBootstrapBuilder(): FormBootstrapBuilder
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Client compatibility `getFormBootstrapBuilder()` has been deprecated. Use `getClientFormBootstrapBuilder()` instead.');

        return $this->plugin->getClientFormBootstrapBuilder();
    }

    public function getFormDefinitionBuilder(): FormDefinitionBuilder
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Client compatibility `getFormDefinitionBuilder()` has been deprecated. Use `getClientFormDefinitionBuilder()` instead.');

        return $this->plugin->getClientFormDefinitionBuilder();
    }

    public function getHtmlPayloadBuilder(): ServerRenderPayloadBuilder
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Client compatibility `getHtmlPayloadBuilder()` has been deprecated. Use `getServerRenderPayloadBuilder()` instead.');

        return $this->plugin->getServerRenderPayloadBuilder();
    }

    public function getModuleManifestBuilder(): ClientModuleManifestBuilder
    {
        Craft::$app->getDeprecator()->log(__METHOD__, 'Client compatibility `getModuleManifestBuilder()` has been deprecated. Use `getClientModuleManifestBuilder()` instead.');

        return $this->plugin->getClientModuleManifestBuilder();
    }
}
