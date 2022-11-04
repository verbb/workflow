<?php
namespace verbb\workflow\gql\queries;

use verbb\workflow\gql\arguments\SubmissionArguments;
use verbb\workflow\gql\interfaces\SubmissionInterface;
use verbb\workflow\gql\resolvers\SubmissionResolver;
use verbb\workflow\helpers\Gql as GqlHelper;

use craft\gql\base\Query;

use GraphQL\Type\Definition\Type;

class SubmissionQuery extends Query
{
    // Static Methods
    // =========================================================================

    public static function getQueries(bool $checkToken = true): array
    {
        if ($checkToken && !GqlHelper::canQuerySubmissions()) {
            return [];
        }

        return [
            'workflowSubmissions' => [
                'type' => Type::listOf(SubmissionInterface::getType()),
                'args' => SubmissionArguments::getArguments(),
                'resolve' => SubmissionResolver::class . '::resolve',
                'description' => 'This query is used to query for workflow submissions.',
            ],
        ];
    }
}
