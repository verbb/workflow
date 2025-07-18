<?php
namespace verbb\workflow\elements\conditions;

use Craft;
use craft\base\conditions\BaseMultiSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\conditions\ElementConditionRuleInterface;

use yii\db\QueryInterface;

class RoleConditionRule extends BaseMultiSelectConditionRule implements ElementConditionRuleInterface
{
    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('workflow', 'Role');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['role'];
    }

    public function modifyQuery(QueryInterface $query): void
    {
        /** @var ElementQueryInterface $query */
        $query->role($this->paramValue());
    }

    public function matchElement(ElementInterface $element): bool
    {
        return $this->matchValue($element->getRole());
    }


    // Protected Methods
    // =========================================================================

    protected function options(): array
    {
        return [
            ['label' => Craft::t('workflow', 'Editor'), 'value' => 'editor'],
            ['label' => Craft::t('workflow', 'Reviewer'), 'value' => 'Rreviewer'],
            ['label' => Craft::t('workflow', 'Publisher'), 'value' => 'publisher'],
        ];
    }
}
