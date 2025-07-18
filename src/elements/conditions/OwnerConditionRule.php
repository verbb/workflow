<?php
namespace verbb\workflow\elements\conditions;

use Craft;
use craft\base\conditions\BaseElementSelectConditionRule;
use craft\base\ElementInterface;
use craft\elements\Entry;
use craft\elements\db\ElementQueryInterface;
use craft\elements\conditions\ElementConditionRuleInterface;

use yii\db\QueryInterface;

class OwnerConditionRule extends BaseElementSelectConditionRule implements ElementConditionRuleInterface
{
    // Properties
    // =========================================================================

    public string $elementType = Entry::class;


    // Public Methods
    // =========================================================================

    public function getLabel(): string
    {
        return Craft::t('workflow', 'Entry');
    }

    public function getExclusiveQueryParams(): array
    {
        return ['ownerId'];
    }

    public function modifyQuery(ElementQueryInterface $query): void
    {
        $elementIds = $this->getElementIds();

        if (!empty($elementIds)) {
            $query->ownerId($elementIds);
        }
    }

    public function matchElement(ElementInterface $element): bool
    {
        $elementIds = $this->getElementIds();

        if (empty($elementIds)) {
            return true;
        }

        return $element::find()
            ->id($element->id ?: false)
            ->site('*')
            ->drafts($element->getIsDraft())
            ->provisionalDrafts($element->isProvisionalDraft)
            ->revisions($element->getIsRevision())
            ->status(null)
            ->ownerId($elementIds)
            ->exists();
    }
    

    // Protected Methods
    // =========================================================================

    protected function elementType(): string
    {
        return $this->elementType;
    }
}
