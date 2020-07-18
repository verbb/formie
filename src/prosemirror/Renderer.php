<?php

namespace verbb\formie\prosemirror;

use craft\helpers\Json;

class Renderer
{
    protected $document;

    protected $nodes = [
        Nodes\Blockquote::class,
        Nodes\BulletList::class,
        Nodes\CodeBlock::class,
        Nodes\HardBreak::class,
        Nodes\Heading::class,
        Nodes\ListItem::class,
        Nodes\OrderedList::class,
        Nodes\Paragraph::class,
        Nodes\Table::class,
        Nodes\TableCell::class,
        Nodes\TableHeader::class,
        Nodes\TableRow::class,
    ];

    protected $marks = [
        Marks\Bold::class,
        Marks\Code::class,
        Marks\Italic::class,
        Marks\Link::class,
        Marks\Subscript::class,
        Marks\Underline::class,
        Marks\Superscript::class,
    ];

    public function document($value)
    {
        if (is_string($value)) {
            $value = Json::decode($value, false);
        } elseif (is_array($value)) {
            $value = Json::decode(Json::encode($value), false);
        }

        $this->document = $value;

        return $this;
    }

    private function renderNode($node)
    {
        $html = [];

        if (isset($node->marks)) {
            foreach ($node->marks as $mark) {
                foreach ($this->marks as $class) {
                    $renderClass = new $class($mark);

                    if ($renderClass->matching()) {
                        $html[] = $this->renderOpeningTag($renderClass->tag());
                    }
                }
            }
        }

        foreach ($this->nodes as $class) {
            $renderClass = new $class($node);

            if ($renderClass->matching()) {
                $html[] = $this->renderOpeningTag($renderClass->tag());
                break;
            }
        }

        if (isset($node->content)) {
            foreach ($node->content as $nestedNode) {
                $html[] = $this->renderNode($nestedNode);
            }
        } elseif (isset($node->text)) {
            $html[] = htmlentities($node->text, ENT_QUOTES);
        } elseif ($text = $renderClass->text()) {
            $html[] = $text;
        }

        foreach ($this->nodes as $class) {
            $renderClass = new $class($node);

            if ($renderClass->selfClosing()) {
                continue;
            }

            if ($renderClass->matching()) {
                $html[] = $this->renderClosingTag($renderClass->tag());
            }
        }

        if (isset($node->marks)) {
            foreach (array_reverse($node->marks) as $mark) {
                foreach ($this->marks as $class) {
                    $renderClass = new $class($mark);

                    if ($renderClass->matching()) {
                        $html[] = $this->renderClosingTag($renderClass->tag());
                    }
                }
            }
        }

        return join($html);
    }

    private function renderOpeningTag($tags)
    {
        $tags = (array) $tags;

        if (!$tags || !count($tags)) {
            return null;
        }

        return join('', array_map(function ($item) {
            if (is_string($item)) {
                return "<{$item}>";
            }

            $attrs = '';
            if (isset($item['attrs'])) {
                foreach ($item['attrs'] as $attribute => $value) {
                    $attrs .= " {$attribute}=\"{$value}\"";
                }
            }

            return "<{$item['tag']}{$attrs}>";
        }, $tags));
    }

    private function renderClosingTag($tags)
    {
        $tags = (array) $tags;
        $tags = array_reverse($tags);

        if (!$tags || !count($tags)) {
            return null;
        }

        return join('', array_map(function ($item) {
            if (is_string($item)) {
                return "</{$item}>";
            }

            return "</{$item['tag']}>";
        }, $tags));
    }

    public function render($value)
    {
        $this->document($value);

        $html = [];

        foreach ($this->document->content as $node) {
            $html[] = $this->renderNode($node);
        }

        return join($html);
    }

    public function addNode($node)
    {
        $this->nodes[] = $node;
    }

    public function addNodes($nodes)
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }
    }

    public function addMark($mark)
    {
        $this->marks[] = $mark;
    }

    public function addMarks($marks)
    {
        foreach ($marks as $mark) {
            $this->addMark($mark);
        }
    }
}
