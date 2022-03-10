<?php
namespace verbb\formie\prosemirror\tohtml;

use craft\helpers\Json;

class Renderer
{
    protected mixed $document = null;

    protected array $nodes = [
        Nodes\Blockquote::class,
        Nodes\BulletList::class,
        Nodes\CodeBlock::class,
        Nodes\HardBreak::class,
        Nodes\Heading::class,
        Nodes\HorizontalRule::class,
        Nodes\Iframe::class,
        Nodes\Image::class,
        Nodes\ListItem::class,
        Nodes\OrderedList::class,
        Nodes\Paragraph::class,
        Nodes\Table::class,
        Nodes\TableCell::class,
        Nodes\TableHeader::class,
        Nodes\TableRow::class,
    ];

    protected array $marks = [
        Marks\Bold::class,
        Marks\Code::class,
        Marks\Italic::class,
        Marks\Link::class,
        Marks\Subscript::class,
        Marks\Underline::class,
        Marks\Strike::class,
        Marks\Superscript::class,
    ];

    public function withMarks($marks = null): static
    {
        if (is_array($marks)) {
            $this->marks = $marks;
        }

        return $this;
    }

    public function withNodes($nodes = null): static
    {
        if (is_array($nodes)) {
            $this->nodes = $nodes;
        }

        return $this;
    }

    public function document($value): static
    {
        if (is_string($value)) {
            $value = Json::decode($value);
        } else if (is_array($value)) {
            $value = Json::decode(Json::encode($value), false);
        }

        $this->document = $value;

        return $this;
    }

    public function render($value): string
    {
        $this->document($value);

        $html = [];

        $content = is_array($this->document->content) ? $this->document->content : [];

        foreach ($content as $node) {
            $html[] = $this->renderNode($node);
        }

        return implode($html);
    }

    public function addNode($node): static
    {
        $this->nodes[] = $node;

        return $this;
    }

    public function addNodes($nodes): static
    {
        foreach ($nodes as $node) {
            $this->addNode($node);
        }

        return $this;
    }

    public function addMark($mark): static
    {
        $this->marks[] = $mark;

        return $this;
    }

    public function addMarks($marks): static
    {
        foreach ($marks as $mark) {
            $this->addMark($mark);
        }

        return $this;
    }

    public function replaceNode($search_node, $replace_node): static
    {
        foreach ($this->nodes as $key => $node_class) {
            if ($node_class == $search_node) {
                $this->nodes[$key] = $replace_node;
            }
        }

        return $this;
    }

    public function replaceMark($search_mark, $replace_mark): static
    {
        foreach ($this->marks as $key => $mark_class) {
            if ($mark_class == $search_mark) {
                $this->marks[$key] = $replace_mark;
            }
        }

        return $this;
    }

    private function renderNode($node): string
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
        } else if (isset($node->text)) {
            $html[] = $node->text;
        } else if ($text = $renderClass->text()) {
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

        return implode($html);
    }

    private function renderOpeningTag($tags): ?string
    {
        $tags = (array)$tags;

        if (!$tags || !count($tags)) {
            return null;
        }

        return implode('', array_map(function($item) {
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

    private function renderClosingTag($tags): ?string
    {
        $tags = (array)$tags;
        $tags = array_reverse($tags);

        if (!$tags || !count($tags)) {
            return null;
        }

        return implode('', array_map(function($item) {
            if (is_string($item)) {
                return "</{$item}>";
            }

            return "</{$item['tag']}>";
        }, $tags));
    }
}
