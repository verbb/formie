<?php

namespace verbb\formie\prosemirror\toprosemirror\Nodes;

class CodeBlock extends Node
{
    public function matching(): bool
    {
        return
            $this->DOMNode->nodeName === 'code' &&
            $this->DOMNode->parentNode->nodeName === 'pre';
    }

    private function getLanguage(): array|string|null
    {
        return preg_replace("/^language-/", "", $this->DOMNode->getAttribute('class'));
    }

    public function data(): ?array
    {
        if ($language = $this->getLanguage()) {
            return [
                'type' => 'code_block',
                'attrs' => [
                    'language' => $this->getLanguage(),
                ],
            ];
        }

        return [
            'type' => 'code_block',
        ];
    }
}
