<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class VariableTag extends Node
{
    public function matching(): bool
    {
        return $this->DOMNode->nodeName === 'variable-tag';
    }

    public function data(): ?array
    {
        return [
            'type' => 'variableTag',
            'attrs' => [
                'value' => $this->DOMNode->getAttribute('value'),
                'label' => $this->DOMNode->getAttribute('label'),
            ],
        ];
    }
}
