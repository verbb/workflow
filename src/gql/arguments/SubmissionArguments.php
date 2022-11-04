<?php
namespace verbb\workflow\gql\arguments;

use verbb\workflow\elements\Submission;

use Craft;
use craft\base\GqlInlineFragmentFieldInterface;
use craft\gql\base\ElementArguments;
use craft\gql\types\QueryArgument;

use GraphQL\Type\Definition\Type;

class SubmissionArguments extends ElementArguments
{
    // Static Methods
    // =========================================================================

    public static function getArguments(): array
    {
        return array_merge(parent::getArguments(), self::getContentArguments(), [
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::listOf(QueryArgument::getType()),
                'description' => 'Narrows the query results based on the owner element the submission was made on, per the owners’ IDs.',
            ],
        ]);
    }

    public static function getContentArguments(): array
    {
        $contentArguments = [];

        $contentFields = Craft::$app->getFields()->getLayoutByType(Submission::class)->getCustomFields();

        foreach ($contentFields as $contentField) {
            if (!$contentField instanceof GqlInlineFragmentFieldInterface) {
                $contentArguments[$contentField->handle] = $contentField->getContentGqlQueryArgumentType();
            }
        }

        return array_merge(parent::getContentArguments(), $contentArguments);
    }

    public static function getRevisionArguments(): array
    {
        return [];
    }
}
