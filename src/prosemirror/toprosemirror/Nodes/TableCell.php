<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class TableCell extends Node
{
    protected string|null|array $tagName = 'td';
    protected string $nodeType = 'table_cell';

    public function matching(): bool
    {
        return $this->DOMNode->nodeName === $this->tagName;
    }

    public function data(): ?array
    {
        $data = [
            'type' => $this->nodeType,
        ];

        $attrs = [];
        if ($colspan = $this->DOMNode->getAttribute('colspan')) {
            $attrs['colspan'] = (int)$colspan;
        }
        if ($colwidth = $this->DOMNode->getAttribute('data-colwidth')) {
            $widths = array_map(function($w) {
                return (int)$w;
            }, explode(',', $colwidth));
            if (count($widths) === $attrs['colspan']) {
                $attrs['colwidth'] = $widths;
            }
        }
        if ($rowspan = $this->DOMNode->getAttribute('rowspan')) {
            $attrs['rowspan'] = (int)$rowspan;
        }

        if (!empty($attrs)) {
            $data['attrs'] = $attrs;
        }

        return $data;
    }
}
