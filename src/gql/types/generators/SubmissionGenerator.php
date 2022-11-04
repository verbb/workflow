<?php
namespace verbb\workflow\gql\types\generators;

use verbb\workflow\elements\Submission;
use verbb\workflow\gql\interfaces\SubmissionInterface;
use verbb\workflow\gql\types\SubmissionType;

use Craft;
use craft\gql\base\Generator;
use craft\gql\base\GeneratorInterface;
use craft\gql\base\ObjectType;
use craft\gql\base\SingleGeneratorInterface;
use craft\gql\GqlEntityRegistry;

class SubmissionGenerator extends Generator implements GeneratorInterface, SingleGeneratorInterface
{
    // Static Methods
    // =========================================================================

    public static function generateTypes(mixed $context = null): array
    {
        $type = static::generateType($context);
        return [$type->name => $type];
    }

    public static function generateType(mixed $context): ObjectType
    {
        $context = $context ?: Craft::$app->getFields()->getLayoutByType(Submission::class);

        $typeName = Submission::gqlTypeNameByContext(null);
        $contentFieldGqlTypes = self::getContentFields($context);
        $submissionFields = array_merge(SubmissionInterface::getFieldDefinitions(), $contentFieldGqlTypes);

        return GqlEntityRegistry::getEntity($typeName) ?: GqlEntityRegistry::createEntity($typeName, new SubmissionType([
            'name' => $typeName,
            'fields' => function() use ($submissionFields, $typeName) {
                return Craft::$app->getGql()->prepareFieldDefinitions($submissionFields, $typeName);
            },
        ]));
    }
}
