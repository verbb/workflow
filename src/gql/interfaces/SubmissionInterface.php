<?php
namespace verbb\workflow\gql\interfaces;

use verbb\workflow\gql\types\generators\SubmissionGenerator;

use Craft;
use craft\gql\interfaces\Element;
use craft\gql\GqlEntityRegistry;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;

class SubmissionInterface extends Element
{
    // Static Methods
    // =========================================================================

    public static function getTypeGenerator(): string
    {
        return SubmissionGenerator::class;
    }

    public static function getType(): Type
    {
        if ($type = GqlEntityRegistry::getEntity(self::getName())) {
            return $type;
        }

        $type = GqlEntityRegistry::createEntity(self::getName(), new InterfaceType([
            'name' => static::getName(),
            'fields' => self::class . '::getFieldDefinitions',
            'description' => 'This is the interface implemented by all submissions.',
            'resolveType' => self::class . '::resolveElementTypeName',
        ]));

        SubmissionGenerator::generateTypes();

        return $type;
    }

    public static function getName(): string
    {
        return 'SubmissionInterface';
    }

    public static function getFieldDefinitions(): array
    {
        return Craft::$app->getGql()->prepareFieldDefinitions(array_merge(parent::getFieldDefinitions(), [
            'ownerId' => [
                'name' => 'ownerId',
                'type' => Type::int(),
                'description' => 'The ID of the element that the submission relates to.',
            ],
            'owner' => [
                'name' => 'owner',
                'type' => Element::getType(),
                'description' => 'The element that the submission relates to.',
            ],
        ]), self::getName());
    }
}
