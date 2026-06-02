<?php
namespace verbb\formie\gql\queries;

use verbb\formie\gql\resolvers\HtmlFormResolver;
use verbb\formie\gql\types\FormServerRenderedPayloadType;
use verbb\formie\gql\types\input\ServerRenderPayloadInputType;
use verbb\formie\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class HtmlFormQuery extends Query
{
    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQueryForms()) {
            return [];
        }

        return [
            'formieHtmlForm' => [
                'type' => FormServerRenderedPayloadType::getType(),
                'args' => [
                    'handle' => Type::nonNull(Type::string()),
                    'siteId' => Type::int(),
                    'input' => ServerRenderPayloadInputType::getType(),
                ],
                'resolve' => HtmlFormResolver::class . '::resolve',
                'description' => 'Return the HTML render payload.',
                'complexity' => GqlHelper::singleQueryComplexity(),
            ],
        ];
    }
}
