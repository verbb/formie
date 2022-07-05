<?php
namespace verbb\formie\web\twig\nodes;

use verbb\formie\helpers\Html;

use Twig\Compiler;
use Twig\Node\Node;

class FieldTagNode extends Node
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
            ->write("\$_field = \$context['field'] ?? null;\n")
            ->write("if (isset(\$_field)) {\n")
            ->indent()
            ->write("\$_htmlTag = \$_field->renderHtmlTag(")
            ->subcompile($this->getNode('name'))
            ->write(", \$context);\n")
            ->write("if (isset(\$_htmlTag)) {\n")
            ->indent();

        // Allow options passed in with `with` to override attributes
        if ($this->hasNode('options')) {
            $compiler
                ->write("\$_attributes = " . Html::class . "::mergeAttributes(")
                ->subcompile($this->getNode('options'))
                ->write(", \$_htmlTag->attributes);\n");
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
            // But for some keys we **don't** want to do this. For example, disabling a label, you'd
            // want to also remove the inner text.
            ->write("\$_destroyableKeys = ['fieldLabel', 'fieldInstructions', 'fieldInput', 'fieldAddButton'];\n")
            ->write("if (!in_array(")
            ->subcompile($this->getNode('name'))
            ->write(", \$_destroyableKeys)) {\n")
            ->indent()
            ->write("echo \$_content;\n")
            ->outdent()
            ->write("}\n")

            ->outdent()
            ->write("}\n")
            ->outdent()
            ->write("}\n");
    }
}
