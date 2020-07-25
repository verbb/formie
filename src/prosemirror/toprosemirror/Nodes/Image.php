<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class Image extends Node
{
    public function matching()
    {
        return $this->DOMNode->nodeName === 'img';
    }

    public function data()
    {
        return [
            'type' => 'text',
            'text' => $this->DOMNode->getAttribute('src'),
            'marks' => [
                [
                    'type' => 'link',
                    'attrs' => [
                        'href' => $this->DOMNode->getAttribute('src'),
                    ],
                ],
            ],
        ];
    }
}
