<?php
namespace verbb\formie\models;

use verbb\formie\elements\Submission;
use verbb\formie\Formie;
use verbb\formie\helpers\References;
use verbb\tiptap\Normalizer;
use verbb\tiptap\RichText as TiptapRichText;

use JsonSerializable;
use Stringable;

class RichText implements JsonSerializable, Stringable
{
    private TiptapRichText $_content;

    public static function from(mixed $value = null): self
    {
        return $value instanceof self ? $value : new self($value);
    }

    public static function fromHtml(string $html): self
    {
        return new self(TiptapRichText::fromHtml($html, self::_getAdditionalExtensions()));
    }

    public static function normalizeNodes(mixed $content): array|string
    {
        if (is_array($content) || is_object($content)) {
            return Normalizer::normalize($content);
        }

        if (is_string($content)) {
            return Normalizer::stripInvisibleChars($content);
        }

        return Normalizer::normalize($content);
    }

    public function __construct(mixed $value = null)
    {
        $this->_content = TiptapRichText::from($value);
    }

    public function __toString(): string
    {
        return $this->toPlainText();
    }

    public function setValue(mixed $value): void
    {
        $this->_content = TiptapRichText::from($value);
    }

    public function isEmpty(): bool
    {
        return $this->_content->isEmpty();
    }

    public function getValue(): array
    {
        return $this->_content->getSchema();
    }

    public function getSchema(): array
    {
        return $this->_content->getSchema();
    }

    public function toDoc(): array
    {
        return $this->_content->toDoc();
    }

    public function toJson(): string
    {
        return $this->_content->toJson();
    }

    public function toHtml(?Submission $submission = null, bool $nl2br = true): string
    {
        return $this->_content->toHtml(
            resolveReferences: $submission
                ? fn (string $html): string => (string)References::parseContent($html, $submission)
                : null,
            nl2br: $nl2br,
            additionalExtensions: self::_getAdditionalExtensions(),
        );
    }

    public function toPlainText(?Submission $submission = null): string
    {
        return $this->_content->toPlainText(
            resolveReferences: $submission
                ? fn (string $text): string => (string)References::parseContent($text, $submission)
                : null,
        );
    }

    public function getHtml(?Submission $submission = null, bool $nl2br = true): string
    {
        return $this->toHtml($submission, $nl2br);
    }

    public function getString(?Submission $submission = null): string
    {
        return $this->toPlainText($submission);
    }

    public function jsonSerialize(): array
    {
        return $this->_content->jsonSerialize();
    }


    // Private Methods
    // =========================================================================

    private static function _getAdditionalExtensions(): array
    {
        return Formie::$plugin?->getTiptapExtensions()->getPhpExtensions() ?? [];
    }
}
