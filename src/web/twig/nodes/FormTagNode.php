<?php
namespace verbb\formie\web\twig\nodes;

use verbb\formie\helpers\Html;

use Twig\Compiler;
use Twig\Node\Node;

class FormTagNode extends Node
{
    /**
     * @inheritdoc
     */
    public function compile(Compiler $compiler): void
    {
        $compiler->addDebugInfo($this);

        $compiler
            ->write("ob_start();\n")
            ->subcompile($this->getNode('content'))
            ->write("\$_content = ob_get_clean();\n")
            ->write("\$_form = \$context['form'] ?? null;\n")
            ->write("if (isset(\$_form)) {\n")
            ->indent()
            ->write("\$_htmlTag = \$_form->renderHtmlTag(")
            ->subcompile($this->getNode('name'))
            ->write(", \$context);\n")
            ->write("if (isset(\$_htmlTag)) {\n")
            ->indent();

        // Allow options passed in with `with` to override attributes
        if ($this->hasNode('options')) {
            $compiler
                ->write("\$_attributes = " . Html::class . "::mergeAttributes(\$_htmlTag->attributes, ")
                ->subcompile($this->getNode('options'))
                ->write(");\n");
        } else {
            $compiler
                ->write("\$_attributes = \$_htmlTag->attributes;\n");
        }

        $compiler
            ->write("echo " . Html::class . "::tag(\$_htmlTag->tag, \$_content, \$_attributes);\n")
            ->outdent()
            ->write("} else {\n")
            ->indent()

            // If `renderHtmlTag()` returns `null` ensure we print out the inner content still.
            // That's because we're wanting to not render the HTML element, but still want what's inside.
            ->write("echo \$_content;\n")
            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n");
    }
}
