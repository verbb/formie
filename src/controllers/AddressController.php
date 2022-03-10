<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;

use Craft;
use craft\helpers\Json;
use craft\web\Controller;

use GuzzleHttp\Exception\RequestException;

use Throwable;

use yii\web\Response;

class AddressController extends Controller
{
    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['google-places-geocode'];


    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if ($action->id === 'google-places-geocode') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionGooglePlacesGeocode(): Response
    {
        // Provide a proxy for Google Placed Geocoding lookup, which can't be done in client-side code without
        // using an un-restricted API key, which is bad seeing as though it's publicly scrape-able.
        $this->requireAcceptsJson();

        $request = Craft::$app->getRequest();
        $guzzleClient = Craft::createGuzzleClient();

        try {
            $response = $guzzleClient->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'latlng' => $request->getParam('latlng'),
                    'key' => $request->getParam('key'),
                ],
            ]);

            $result = Json::decode((string)$response->getBody(), true);

            return $this->asJson($result);
        } catch (Throwable $e) {
            $messageText = $e->getMessage();

            // Check for Guzzle errors, which are truncated in the exception `getMessage()`.
            if ($e instanceof RequestException && $e->getResponse()) {
                $messageText = (string)$e->getResponse()->getBody()->getContents();
            }

            $message = Craft::t('formie', 'Support request error: “{message}” {file}:{line}', [
                'message' => $messageText,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            Formie::error($message);

            return $this->asJson(['error' => $message]);
        }
    }
}
