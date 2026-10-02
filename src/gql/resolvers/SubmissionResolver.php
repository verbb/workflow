<?php
namespace verbb\workflow\gql\resolvers;

use verbb\workflow\elements\Submission;
use verbb\workflow\elements\db\SubmissionQuery;
use verbb\workflow\helpers\Gql as GqlHelper;

use Craft;
use craft\elements\db\ElementQuery;
use craft\elements\ElementCollection;
use craft\gql\base\ElementResolver;
use craft\helpers\Db;

use yii\db\Expression;

class SubmissionResolver extends ElementResolver
{
    // Static Methods
    // =========================================================================

    public static function prepareQuery(mixed $source, array $arguments, ?string $fieldName = null): mixed
    {
        if ($source === null) {
            $query = Submission::find();
        } else {
            $query = $source->$fieldName;
        }

        if (!$query instanceof ElementQuery) {
            return $query;
        }

        foreach ($arguments as $key => $value) {
            $query->$key($value);
        }

        if (!GqlHelper::canQuerySubmissions()) {
            return ElementCollection::empty();
        }

        if (!$query instanceof SubmissionQuery) {
            return $query;
        }

        $ownerQueries = GqlHelper::getSubmissionOwnerQueries();

        if ($ownerQueries === []) {
            return ElementCollection::empty();
        }

        // Filter by the exact owner localization before GraphQL applies pagination.
        $ownerConditions = ['or'];

        foreach ($ownerQueries as $ownerQuery) {
            $ownerQuery->withCustomFields(false);
            $ownerExistsQuery = $ownerQuery->prepare(Craft::$app->getDb()->getQueryBuilder());
            $ownerExistsQuery
                ->select(new Expression('1'))
                ->andWhere(new Expression('[[elements.id]] = [[workflow_submissions.ownerId]]'))
                ->andWhere(new Expression('[[elements_sites.siteId]] = [[workflow_submissions.ownerSiteId]]'));

            $ownerConditions[] = ['exists', $ownerExistsQuery];
        }

        $query->andWhere($ownerConditions);

        return $query;
    }
}
