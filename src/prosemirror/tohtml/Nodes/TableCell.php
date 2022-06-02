<?php

namespace verbb\formie\prosemirror\tohtml\Nodes;

class TableCell extends Node
{
    protected ?string $nodeType = 'tableCell';
    protected string|null|array $tagName = 'td';

    public function tag(): array
    {
        $attrs = [];
        if (isset($this->node->attrs)) {
            if (isset($this->node->attrs->colspan)) {
                $attrs['colspan'] = $this->node->attrs->colspan;
            }
            if (isset($this->node->attrs->colwidth)) {
                if ($widths = $this->node->attrs->colwidth) {
                    if (count($widths) === $attrs['colspan']) {
                        $attrs['data-colwidth'] = implode(',', $widths);
                    }
                }
            }
            if (isset($this->node->attrs->rowspan)) {
                $attrs['rowspan'] = $this->node->attrs->rowspan;
            }
        }

        return [
            [
                'tag' => $this->tagName,
                'attrs' => $attrs,
            ],
        ];
    }
}
