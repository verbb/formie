<?php
namespace verbb\formie\web\twig;

use verbb\formie\Formie;
use verbb\formie\helpers\RichTextHelper;

use Twig_Extension;
use Twig_SimpleFunction;
use Twig_SimpleFilter;

class Extension extends Twig_Extension
{
    // Public Methods
    // =========================================================================

    public function getName(): string
    {
        return 'Formie Variables';
    }

    public function getFunctions(): array
    {
        return [
            new Twig_SimpleFunction('getRichTextConfig', [new RichTextHelper(), 'getRichTextConfig']),
        ];
    }
}
