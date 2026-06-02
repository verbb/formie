<?php
namespace verbb\formie\models;

use verbb\formie\elements\Submission;
use verbb\formie\helpers\References;
use verbb\formie\helpers\VariableNode;
use verbb\formie\prosemirror\tohtml\Renderer as HtmlRenderer;
use verbb\formie\prosemirror\toprosemirror\Renderer as ProseMirrorRenderer;

use Craft;
use craft\helpers\Json;

use JsonSerializable;

class RichText implements JsonSerializable
{
    // Static Methods
    // =========================================================================

    public static function from(mixed $value = null): self
    {
        return $value instanceof self ? $value : new self($value);
    }

    public static function normalizeNodes(mixed $content): array|string
    {
        if (is_array($content)) {
            return self::_pruneEmptyTextNodes(self::_normalizeNodeValue($content));
        }

        if (is_object($content)) {
            return self::_pruneEmptyTextNodes(self::_normalizeNodeValue((array)$content));
        }

        return (string)self::_normalizeNodeValue($content);
    }


    // Properties
    // =========================================================================

    private const INVISIBLE_CHARS = [
        "\u{200B}",
        "\u{200C}",
        "\u{200D}",
        "\u{2060}",
        "\u{FEFF}",
    ];

    private array $_value = [];


    // Public Methods
    // =========================================================================

    public function __construct(mixed $value = null)
    {
        $this->setValue($value);
    }

    public function __toString(): string
    {
        return $this->toPlainText();
    }

    public function setValue(mixed $value): void
    {
        $this->_value = $this->_normalizeValue($value);
    }

    public function isEmpty(): bool
    {
        return $this->_value === [];
    }

    public function getValue(): array
    {
        return $this->_value;
    }

    public function getSchema(): array
    {
        return $this->_value;
    }

    public function toDoc(): array
    {
        return [
            'type' => 'doc',
            'content' => $this->getSchema(),
        ];
    }

    public function toJson(): string
    {
        return Json::encode($this->getSchema());
    }

    public function toHtml(?Submission $submission = null, bool $nl2br = true): string
    {
        if ($this->isEmpty()) {
            return '';
        }

        $renderer = new HtmlRenderer();
        $renderer->addNode(VariableNode::class);

        $html = $renderer->render($this->toDoc());

        if ($nl2br) {
            $html = str_replace(['<p>', '</p>'], ['', '<br>'], $html);
            $html = preg_replace('/(<br>)+$/', '', $html);
        }

        if ($submission) {
            $html = Craft::t('formie', $html);
            $html = References::parseContent($html, $submission);
        }

        return self::_stripInvisibleChars(html_entity_decode($html));
    }

    public function toPlainText(?Submission $submission = null): string
    {
        return self::_stripInvisibleChars(Craft::t('formie', strip_tags($this->toHtml($submission, false))));
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
        return $this->getSchema();
    }


    // Private Methods
    // =========================================================================

    private function _normalizeValue(mixed $value): array
    {
        if ($value instanceof self) {
            return $value->getSchema();
        }

        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = Json::decodeIfJson($value);

            if ($decoded !== $value) {
                return $this->_normalizeValue($decoded);
            }

            if ($this->_isHtml($value)) {
                return $this->_htmlToSchema($value);
            }

            return $this->_createParagraphContent($value);
        }

        if (is_object($value)) {
            return $this->_normalizeValue((array)$value);
        }

        if (is_array($value)) {
            $value = self::normalizeNodes($value);

            if (isset($value['type']) && $value['type'] === 'doc') {
                return $this->_normalizeValue($value['content'] ?? []);
            }

            if (isset($value['type'])) {
                return [$value];
            }

            return $value;
        }

        return $this->_createParagraphContent((string)$value);
    }

    private function _createParagraphContent(string $text): array
    {
        if ($text === '') {
            return [];
        }

        return [
            [
                'type' => 'paragraph',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $text,
                    ],
                ],
            ],
        ];
    }

    private function _htmlToSchema(string $html): array
    {
        $doc = (new ProseMirrorRenderer())->render($html);

        return $this->_normalizeValue($doc['content'] ?? []);
    }

    private function _isHtml(string $value): bool
    {
        return (bool)preg_match('/<\s*[a-z!\/][^>]*>/i', $value);
    }

    private static function _normalizeNodeValue(mixed $value): mixed
    {
        $search = ['bullet_list', 'code_block', 'hard_break', 'horizontal_rule', 'list_item', 'ordered_list'];
        $replace = ['bulletList', 'codeBlock', 'hardBreak', 'horizontalRule', 'listItem', 'orderedList'];

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalizedKey = is_string($key) ? str_replace($search, $replace, $key) : $key;
                $normalized[$normalizedKey] = self::_normalizeNodeValue($item);
            }

            return $normalized;
        }

        if (is_object($value)) {
            return self::_normalizeNodeValue((array)$value);
        }

        if (is_string($value)) {
            return self::_stripInvisibleChars(str_replace($search, $replace, $value));
        }

        return $value;
    }

    private static function _pruneEmptyTextNodes(mixed $value): mixed
    {
        if (is_array($value)) {
            $isList = array_is_list($value);
            $cleaned = [];

            foreach ($value as $key => $item) {
                $cleanedItem = self::_pruneEmptyTextNodes($item);

                if ($cleanedItem === null) {
                    continue;
                }

                if ($isList) {
                    $cleaned[] = $cleanedItem;
                } else {
                    $cleaned[$key] = $cleanedItem;
                }
            }

            if (($cleaned['type'] ?? null) === 'text' && ($cleaned['text'] ?? null) === '') {
                return null;
            }

            return $cleaned;
        }

        if (is_object($value)) {
            return self::_pruneEmptyTextNodes((array)$value);
        }

        return $value;
    }

    private static function _stripInvisibleChars(string $value): string
    {
        return str_replace(self::INVISIBLE_CHARS, '', $value);
    }
}
