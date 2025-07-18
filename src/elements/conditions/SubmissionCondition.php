<?php
namespace verbb\workflow\elements\conditions;

use craft\elements\conditions\ElementCondition;
use craft\elements\conditions\NotRelatedToConditionRule;
use craft\elements\conditions\RelatedToConditionRule;
use craft\elements\conditions\SlugConditionRule;
use craft\elements\conditions\StatusConditionRule as BaseStatusConditionRule;
use craft\helpers\ArrayHelper;

class SubmissionCondition extends ElementCondition
{
    // Protected Methods
    // =========================================================================

    protected function selectableConditionRules(): array
    {
        $rules = parent::selectableConditionRules();

        // Remove unneeded conditions
        ArrayHelper::removeValue($rules, NotRelatedToConditionRule::class);
        ArrayHelper::removeValue($rules, RelatedToConditionRule::class);
        ArrayHelper::removeValue($rules, SlugConditionRule::class);

        $rules[] = OwnerConditionRule::class;
        $rules[] = RoleConditionRule::class;

        return $rules;
    }
}
