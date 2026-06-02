<?php
namespace verbb\formie\web\twig\nodes;

use verbb\formie\helpers\Html;
use verbb\formie\web\twig\Extension;

use Twig\Compiler;
use Twig\Node\Node;

class FormTagNode extends Node
{
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);
        $formVar = '$' . $compiler->getVarName();
        $keyVar = '$' . $compiler->getVarName();
        $htmlTagVar = '$' . $compiler->getVarName();
        $contentVar = '$' . $compiler->getVarName();
        $attributesVar = '$' . $compiler->getVarName();
        $renderedContentVar = '$' . $compiler->getVarName();

        $compiler
            ->write("{$formVar} = \$context['form'] ?? null;\n")
            ->write("if (isset({$formVar})) {\n")
            ->indent()
            ->write("{$keyVar} = ")
            ->subcompile($this->getNode('name'))
            ->raw(";\n")
            ->write("{$htmlTagVar} = {$formVar}->renderSlotTag(")
            ->raw($keyVar)
            ->write(", " . Extension::class . "::createRenderContext(\$context));\n")
            ->write("if (isset({$htmlTagVar})) {\n")
            ->indent()
            ->write("ob_start();\n")
            ->subcompile($this->getNode('content'))
            ->write("{$contentVar} = ob_get_clean();\n");

        // Allow options passed in with `with` to override attributes.
        // `reset: true` strips the default theme layer while preserving core browser and instance attrs.
        if ($this->hasNode('options')) {
            $compiler
                ->write("{$attributesVar} = " . Extension::class . "::mergeTagAttributes({$htmlTagVar}->attributes, ")
                ->subcompile($this->getNode('options'))
                ->write(");\n");
        } else {
            $compiler
                ->write("{$attributesVar} = {$htmlTagVar}->attributes;\n");
        }

        $compiler
            ->write("{$renderedContentVar} = {$htmlTagVar}->composeContent({$contentVar});\n")
            ->write("echo " . Html::class . "::tag({$htmlTagVar}->tag, {$renderedContentVar}, {$attributesVar});\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()

            // If `renderSlotTag()` returns `null` ensure we print out the inner content still.
            // That's because we're wanting to not render the HTML element, but still want what's inside.
            ->write("ob_start();\n")
            ->subcompile($this->getNode('content'))
            ->write("echo ob_get_clean();\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n");
    }
}
