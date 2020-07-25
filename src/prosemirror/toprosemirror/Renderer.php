<?php

namespace verbb\formie\prosemirror\toprosemirror;

use DOMElement;
use DOMDocument;
use Minify_HTML;

class Renderer
{
    protected $document;

    protected $storedMarks = [];

    protected $marks = [
        Marks\Bold::class,
        Marks\Code::class,
        Marks\Italic::class,
        Marks\Link::class,
        Marks\Strike::class,
        Marks\Subscript::class,
        Marks\Superscript::class,
        Marks\Underline::class,
    ];

    protected $nodes = [
        Nodes\Blockquote::class,
        Nodes\BulletList::class,
        Nodes\CodeBlock::class,
        Nodes\CodeBlockWrapper::class,
        Nodes\HardBreak::class,
        Nodes\Heading::class,
        Nodes\Image::class,
        Nodes\ListItem::class,
        Nodes\OrderedList::class,
        Nodes\Paragraph::class,
        Nodes\Table::class,
        Nodes\TableCell::class,
        Nodes\TableHeader::class,
        Nodes\TableRow::class,
        Nodes\TableWrapper::class,
        Nodes\Text::class,
        Nodes\User::class,
    ];

    public function render(string $value): array
    {
        $this->setDocument($value);

        $content = $this->renderChildren(
            $this->getDocumentBody()
        );

        return [
            'type'    => 'doc',
            'content' => $content,
        ];
    }

    private function setDocument(string $value): Renderer
    {
        libxml_use_internal_errors(true);

        $this->document = new DOMDocument;
        $this->document->loadHTML(
            $this->wrapHtmlDocument(
                $this->stripWhitespace($value)
            )
        );

        return $this;
    }

    private function wrapHtmlDocument($value)
    {
        return '<?xml encoding="utf-8" ?>' . $value;
    }

    private function stripWhitespace(string $value): string
    {
        return Minify_HTML::minify($value);
    }

    private function getDocumentBody(): DOMElement
    {
        return $this->document->getElementsByTagName('body')->item(0);
    }

    private function renderChildren($node): array
    {
        $nodes = [];

        foreach ($node->childNodes as $child) {
            if ($class = $this->getMatchingNode($child)) {
                $item = $class->data();

                if ($item === null) {
                    if ($child->hasChildNodes()) {
                        $nodes = array_merge($nodes, $this->renderChildren($child));
                    }
                    continue;
                }

                if ($child->hasChildNodes()) {
                    $item = array_merge($item, [
                        'content' => $this->renderChildren($child),
                    ]);
                }

                if (count($this->storedMarks)) {
                    $item = array_merge($item, [
                        'marks' => $this->storedMarks,
                    ]);
                }

                if ($class->wrapper) {
                    $item['content'] = [
                        array_merge($class->wrapper, [
                            'content' => @$item['content'] ?: [],
                        ]),
                    ];
                }

                array_push($nodes, $item);
            }

            if ($class = $this->getMatchingMark($child)) {
                array_push($this->storedMarks, $class->data());

                if ($child->hasChildNodes()) {
                    $nodes = array_merge($nodes, $this->renderChildren($child));
                }

                array_pop($this->storedMarks);
            }
        }

        return $nodes;
    }

    private function getMatchingNode($item)
    {
        return $this->getMatchingClass($item, $this->nodes);
    }

    private function getMatchingMark($item)
    {
        return $this->getMatchingClass($item, $this->marks);
    }

    private function getMatchingClass($node, $classes)
    {
        foreach ($classes as $class) {
            $instance = new $class($node);

            if ($instance->matching()) {
                return $instance;
            }
        }

        return false;
    }
}
