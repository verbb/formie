<?php
namespace verbb\formie\web\twig\tokenparsers;

use verbb\formie\web\twig\nodes\FormTagNode;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class FormTagTokenParser extends AbstractTokenParser
{
    /**
     * @inheritdoc
     */
    public function getTag(): string
    {
        return 'formtag';
    }

    /**
     * @inheritdoc
     */
    public function parse(Token $token): FormTagNode
    {
        $lineno = $token->getLine();
        $expressionParser = $this->parser->getExpressionParser();
        $stream = $this->parser->getStream();

        $nodes = [
            'name' => $expressionParser->parseExpression(),
        ];

        if ($stream->test(Token::NAME_TYPE, 'with')) {
            $stream->next();
            $nodes['options'] = $expressionParser->parseExpression();
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        $nodes['content'] = $this->parser->subparse(function(Token $token) {
            return $token->test('endformtag');
        }, true);
        
        $stream->expect(Token::BLOCK_END_TYPE);

        return new FormTagNode($nodes, [], $lineno, $this->getTag());
    }
}
