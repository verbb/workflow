<?php
namespace verbb\workflow\variables;

use verbb\workflow\elements\db\SubmissionQuery;
use verbb\workflow\elements\Submission;

use Craft;

class WorkflowVariable
{
    public function submissions($criteria = null): SubmissionQuery
    {
        $query = Submission::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
