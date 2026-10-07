<?php
namespace verbb\formie\gql\resolvers;

use verbb\formie\Formie;
use verbb\formie\helpers\BrowserRequestProfile;
use verbb\formie\helpers\CrossOriginRequestHelper;
use verbb\formie\helpers\Gql as GqlHelper;

use Craft;

use yii\web\NotFoundHttpException;

class HtmlFormResolver
{
    // Static Methods
    // =========================================================================

    public static function resolve(mixed $source, array $arguments): array
    {
        BrowserRequestProfile::enter(true);
        CrossOriginRequestHelper::applyHeaders(Craft::$app->getRequest(), Craft::$app->getResponse());
        $form = GqlHelper::findReadableFormByHandle(
            (string)($arguments['handle'] ?? ''),
            isset($arguments['siteId']) ? (int)$arguments['siteId'] : null
        );

        if (!$form) {
            throw new NotFoundHttpException('Form not found');
        }

        $input = $arguments['input'] ?? [];

        return Formie::$plugin->getServerRenderPayloadBuilder()->buildServerRenderPayload($form, [
            'theme' => $input['theme'] ?? null,
            'themeConfig' => $input['themeConfig'] ?? null,
            'locale' => $input['locale'] ?? null,
            'siteId' => $input['siteId'] ?? ($arguments['siteId'] ?? null),
            'includeCss' => false,
            'includeJs' => false,
            'includeScriptsInline' => true,
            'mode' => 'server-rendered',
        ]);
    }
}
