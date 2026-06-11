<?php
namespace verbb\formie\controllers;

use verbb\formie\Formie;
use verbb\formie\elements\Form;
use verbb\formie\fields\Address as AddressField;
use verbb\formie\integrations\addressproviders\Google;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use craft\web\Controller;

use Throwable;

use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\web\TooManyRequestsHttpException;

class AddressController extends Controller
{
    // Constants
    // =========================================================================

    private const GOOGLE_GEOCODE_RATE_LIMIT = 60;
    private const GOOGLE_GEOCODE_RATE_WINDOW_SECONDS = 60;
    private const SUBDIVISIONS_RATE_LIMIT = 120;
    private const SUBDIVISIONS_RATE_WINDOW_SECONDS = 60;
    private const COUNTRY_FROM_IP_RATE_LIMIT = 60;
    private const COUNTRY_FROM_IP_RATE_WINDOW_SECONDS = 60;


    // Properties
    // =========================================================================

    protected array|bool|int $allowAnonymous = ['google-places-geocode', 'subdivisions', 'country-from-ip'];


    // Public Methods
    // =========================================================================

    public function beforeAction($action): bool
    {
        if (in_array($action->id, ['google-places-geocode', 'subdivisions', 'country-from-ip'], true)) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionSubdivisions(): Response
    {
        $this->requireAcceptsJson();

        $request = $this->request;
        $country = trim((string)$request->getParam('country', ''));
        $optionLabel = trim((string)$request->getParam('optionLabel', 'name'));
        $optionValue = trim((string)$request->getParam('optionValue', 'name'));

        if (!in_array($optionLabel, ['name', 'short'], true)) {
            $optionLabel = 'name';
        }

        if (!in_array($optionValue, ['name', 'short'], true)) {
            $optionValue = 'name';
        }

        $this->_enforceSubdivisionsRateLimit($country);

        $countryCode = Formie::$plugin->getCountries()->resolveCountryCode($country);

        if (!$countryCode) {
            throw new BadRequestHttpException('Invalid country.');
        }

        $metadata = Formie::$plugin->getCountries()->getAddressFormatMetadata($countryCode);
        $subdivisions = Formie::$plugin->getCountries()->getAddressSubdivisions(
            $countryCode,
            null,
            $optionLabel,
            $optionValue,
        );

        return $this->asJson(array_merge($metadata, [
            'subdivisions' => $subdivisions,
        ]));
    }

    public function actionCountryFromIp(): Response
    {
        $this->requireAcceptsJson();

        $this->_enforceCountryFromIpRateLimit();

        $country = Formie::$plugin->getCountries()->getCountryForRequest();

        if (!$country) {
            return $this->asJson([
                'countryCode' => null,
                'countryName' => null,
            ]);
        }

        return $this->asJson($country);
    }

    public function actionGooglePlacesGeocode(): Response
    {
        // Provide a proxy for Google Placed Geocoding lookup, which can't be done in client-side code without
        // using an un-restricted API key, which is bad seeing as though it's publicly scrape-able.
        $this->requirePostRequest();
        $this->requireAcceptsJson();

        $request = $this->request;
        $guzzleClient = Craft::createGuzzleClient();
        $latlng = trim((string)$request->getBodyParam('latlng', ''));
        $formHandle = trim((string)$request->getBodyParam('handle', ''));
        $fieldHandle = trim((string)$request->getBodyParam('fieldHandle', ''));

        $this->_enforceGoogleGeocodeRateLimit($formHandle, $fieldHandle);

        try {
            if (!preg_match('/^-?\d+(?:\.\d+)?,\s*-?\d+(?:\.\d+)?$/', $latlng)) {
                throw new BadRequestHttpException('Invalid geocode request.');
            }

            $form = Formie::$plugin->getForms()->getFormByHandle($formHandle);

            if (!$form) {
                throw new BadRequestHttpException('Invalid geocode request.');
            }

            $field = $form->getFieldByHandle($fieldHandle);

            if (!($field instanceof AddressField)) {
                throw new BadRequestHttpException('Invalid geocode request.');
            }

            $integration = $field->getAddressProviderIntegration();

            if (!($integration instanceof Google)) {
                throw new BadRequestHttpException('Invalid geocode request.');
            }

            $apiKey = trim((string)App::parseEnv($integration->geocodingApiKey ?: $integration->apiKey));

            if ($apiKey === '') {
                throw new BadRequestHttpException('Invalid geocode request.');
            }

            $response = $guzzleClient->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'query' => [
                    'latlng' => $latlng,
                    'key' => $apiKey,
                ],
            ]);

            $result = Json::decode((string)$response->getBody(), true);

            return $this->asJson($result);
        } catch (Throwable $e) {
            Formie::error('Google Places geocode proxy failed: “{message}” {file}:{line}', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->asJson(['error' => Craft::t('formie', 'Unable to geocode location.')]);
        }
    }


    // Private Methods
    // =========================================================================

    private function _enforceSubdivisionsRateLimit(string $country): void
    {
        $ipAddress = Craft::$app->getRequest()->getUserIP();
        $cacheKey = 'formie.address-subdivisions-rate.' . md5($country . '|' . $ipAddress);
        $mutexKey = 'formie.address-subdivisions-rate-lock.' . md5($country . '|' . $ipAddress);
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + self::SUBDIVISIONS_RATE_WINDOW_SECONDS,
                ];
            }

            if ((int)$entry['count'] >= self::SUBDIVISIONS_RATE_LIMIT) {
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, (int)$entry['resetAt'] - $now));

                throw new TooManyRequestsHttpException('Too many subdivision requests. Please try again shortly.');
            }

            $entry['count'] = (int)$entry['count'] + 1;
            $cache->set($cacheKey, $entry, max(1, (int)$entry['resetAt'] - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
    }

    private function _enforceCountryFromIpRateLimit(): void
    {
        $ipAddress = Craft::$app->getRequest()->getUserIP();
        $cacheKey = 'formie.address-country-from-ip-rate.' . md5($ipAddress);
        $mutexKey = 'formie.address-country-from-ip-rate-lock.' . md5($ipAddress);
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + self::COUNTRY_FROM_IP_RATE_WINDOW_SECONDS,
                ];
            }

            if ((int)$entry['count'] >= self::COUNTRY_FROM_IP_RATE_LIMIT) {
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, (int)$entry['resetAt'] - $now));

                throw new TooManyRequestsHttpException('Too many country lookup requests. Please try again shortly.');
            }

            $entry['count'] = (int)$entry['count'] + 1;
            $cache->set($cacheKey, $entry, max(1, (int)$entry['resetAt'] - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
    }

    private function _enforceGoogleGeocodeRateLimit(string $formHandle, string $fieldHandle): void
    {
        $ipAddress = Craft::$app->getRequest()->getUserIP();
        $cacheKey = 'formie.google-geocode-rate.' . md5($formHandle . '|' . $fieldHandle . '|' . $ipAddress);
        $mutexKey = 'formie.google-geocode-rate-lock.' . md5($formHandle . '|' . $fieldHandle . '|' . $ipAddress);
        $cache = Craft::$app->getCache();
        $mutex = Craft::$app->getMutex();
        $now = time();
        $lockAcquired = $mutex?->acquire($mutexKey, 3) ?? false;

        try {
            $entry = $cache->get($cacheKey);

            if (!is_array($entry) || !isset($entry['count'], $entry['resetAt']) || (int)$entry['resetAt'] <= $now) {
                $entry = [
                    'count' => 0,
                    'resetAt' => $now + self::GOOGLE_GEOCODE_RATE_WINDOW_SECONDS,
                ];
            }

            if ((int)$entry['count'] >= self::GOOGLE_GEOCODE_RATE_LIMIT) {
                Craft::$app->getResponse()->getHeaders()->set('Retry-After', (string)max(1, (int)$entry['resetAt'] - $now));

                throw new TooManyRequestsHttpException('Too many geocode requests. Please try again shortly.');
            }

            $entry['count'] = (int)$entry['count'] + 1;
            $cache->set($cacheKey, $entry, max(1, (int)$entry['resetAt'] - $now));
        } finally {
            if ($lockAcquired) {
                $mutex?->release($mutexKey);
            }
        }
    }
}
