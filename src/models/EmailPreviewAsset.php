<?php
namespace verbb\formie\models;

use Craft;
use craft\elements\Asset;
use craft\helpers\UrlHelper;

final class EmailPreviewAsset extends Asset
{
    // Public Methods
    // =========================================================================

    public function init(): void
    {
        parent::init();

        $this->filename = 'sample-upload.txt';
        $this->title = 'Sample upload';
        $this->kind = 'text';
        $this->size = strlen($this->getPreviewContents());
    }

    public function getPreviewContents(): string
    {
        return file_get_contents(dirname(__DIR__) . '/resources/email-preview/sample-upload.txt');
    }

    public function getUrl(mixed $transform = null, ?bool $immediately = null): ?string
    {
        $url = Craft::$app->getAssetManager()->getPublishedUrl(dirname(__DIR__) . '/resources/email-preview/sample-upload.txt', true);

        if ($url === false) {
            return null;
        }

        if (UrlHelper::isRootRelativeUrl($url)) {
            return UrlHelper::cpHost() . $url;
        }

        if (UrlHelper::isProtocolRelativeUrl($url)) {
            return (parse_url(UrlHelper::cpHost(), PHP_URL_SCHEME) ?: 'https') . ':' . $url;
        }

        return UrlHelper::url($url);
    }

    public function getCpEditUrl(): ?string
    {
        return $this->getUrl();
    }

    public function getMimeType(mixed $transform = null): ?string
    {
        return 'text/plain';
    }

    public function beforeSave(bool $isNew): bool
    {
        // Preview values must never become stored assets.
        return false;
    }
}
