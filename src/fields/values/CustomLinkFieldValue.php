<?php
namespace verbb\formie\fields\values;

use verbb\formie\content\FieldStorageCodec;

use craft\fields\data\LinkData;

final class CustomLinkFieldValue extends BaseFieldValue
{
    // Properties
    // =========================================================================

    private readonly array $_parts;
    private readonly string $_url;
    private readonly ?string $_label;


    // Public Methods
    // =========================================================================

    public function __construct(LinkData $link)
    {
        $this->_parts = FieldStorageCodec::assertSafe($link->serialize());
        $this->_url = $link->getUrl();
        $this->_label = $link->getLabel();
    }

    public function __toString(): string
    {
        return $this->_url;
    }

    public function getUrl(): string
    {
        return $this->_url;
    }

    public function getLabel(): ?string
    {
        return $this->_label;
    }

    public function serialize(): array
    {
        return $this->_parts;
    }

    public function toValueArray(): array
    {
        return $this->_parts + ['url' => $this->_url, 'label' => $this->_label];
    }

    public function isEmpty(): bool
    {
        return $this->_url === '';
    }
}
