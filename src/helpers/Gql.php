<?php
namespace verbb\workflow\helpers;

use verbb\workflow\elements\Submission;

use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\gql\resolvers\elements\Entry as EntryResolver;
use craft\helpers\Gql as GqlHelper;
use craft\models\Site;

class Gql extends GqlHelper
{
    // Static Methods
    // =========================================================================

    public static function canQuerySubmissions($schema = null): bool
    {
        $allowedEntities = self::extractAllowedEntitiesFromSchema('read', $schema);

        return isset($allowedEntities['workflowSubmissions']);
    }

    /**
     * Builds owner queries for the active schema's entry, site, and lifecycle scopes.
     * Draft and inactive scopes are independent, so drafts need a separate query when inactive entries are denied.
     */
    public static function getSubmissionOwnerQueries(?int $siteId = null): array
    {
        $query = EntryResolver::prepareQuery(null, []);

        if (!$query instanceof EntryQuery) {
            return [];
        }

        $allowedSiteIds = array_map(
            static fn(Site $site): int => $site->id,
            self::getAllowedSites(),
        );

        if ($siteId !== null) {
            if (!in_array($siteId, $allowedSiteIds, true)) {
                return [];
            }

            $allowedSiteIds = [$siteId];
        }

        if ($allowedSiteIds === []) {
            return [];
        }

        $query
            ->siteId($allowedSiteIds)
            ->revisions(false);

        if (self::canQueryInactiveElements()) {
            $query
                ->status(null)
                ->drafts(self::canQueryDrafts() ? null : false);

            return [$query];
        }

        $liveQuery = clone $query;
        $liveQuery
            ->status(Entry::STATUS_LIVE)
            ->drafts(false);

        if (!self::canQueryDrafts()) {
            return [$liveQuery];
        }

        $draftQuery = clone $query;
        $draftQuery
            ->status(null)
            ->drafts(true);

        return [$liveQuery, $draftQuery];
    }

    public static function resolveSubmissionOwner(Submission $submission): ?Entry
    {
        if ($submission->ownerId === null || $submission->ownerSiteId === null) {
            return null;
        }

        foreach (self::getSubmissionOwnerQueries($submission->ownerSiteId) as $query) {
            if ($owner = $query->id($submission->ownerId)->one()) {
                return $owner;
            }
        }

        return null;
    }
}
