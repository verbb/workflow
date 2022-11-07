<?php
namespace verbb\workflow\services;

use verbb\workflow\elements\Submission;

use Craft;
use craft\elements\Entry;
use craft\helpers\ArrayHelper;

use yii\base\Component;

use Diff\Differ\MapDiffer;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Diff\DiffOp\Diff\Diff;

class Content extends Component
{
    // Public Methods
    // =========================================================================

    public function getContentChangesTotalCount(Submission $submission): int
    {
        return (int)$this->_arrayMultisum($this->getContentChangesCounts($submission));
    }

    public function getContentChangesCounts(Submission $submission): array
    {
        $content = [];

        $reviews = $submission->getReviews();
        $differ = new MapDiffer(true);

        foreach ($reviews as $key => $review) {
            $nextReview = $reviews[$key + 1] ?? [];

            if ($nextReview) {
                $diff = $differ->doDiff($nextReview->data, $review->data);

                $content[] = $this->_convertDiffToCount($diff);
            }
        }

        $content = array_filter($content);

        return $content;
    }

    public function getDiff(array $oldArray, array $newArray): array
    {
        $differ = new MapDiffer(true);
        $diff = $differ->doDiff($oldArray, $newArray);

        return $this->_convertDiffToArray($diff);
    }

    public function getRevisionData(Entry $revision): array
    {
        $revisionData = [
            'sectionId' => $revision->sectionId,
            'typeId' => $revision->typeId,
            'authorId' => $revision->authorId,
            'title' => $revision->title,
            'slug' => $revision->slug,
            'postDate' => $revision->postDate ? $revision->postDate->getTimestamp() : null,
            'expiryDate' => $revision->expiryDate ? $revision->expiryDate->getTimestamp() : null,
            'enabled' => $revision->enabled,
            'fields' => [],
        ];

        $content = $revision->getSerializedFieldValues();

        foreach (Craft::$app->getFields()->getAllFields() as $field) {
            if (isset($content[$field->handle]) && $content[$field->handle] !== null) {
                $revisionData['fields'][$field->handle . ':' . $field->id] = $content[$field->handle];
            }
        }

        return $revisionData;
    }


    // Private Methods
    // =========================================================================

    private function _convertDiffToArray(array $array)
    {
        $newArray = [];

        foreach ($array as $attribute => $value) {
            if ($value instanceof Diff) {
                $newArray[$attribute] = $this->_convertDiffToArray($value->getOperations());
            } else {
                $newArray[$attribute] = $value->toArray();
            }
        }

        return $newArray;
    }

    private function _convertDiffToTypedArray(array $array)
    {
        $newArray = [];

        foreach ($array as $attribute => $value) {
            if ($value instanceof DiffOpAdd) {
                $newArray['add'][$attribute] = $value->toArray();
            } else if ($value instanceof DiffOpChange) {
                $newArray['change'][$attribute] = $value->toArray();
            } else if ($value instanceof DiffOpRemove) {
                $newArray['remove'][$attribute] = $value->toArray();
            } else if ($value instanceof Diff) {
                $items = $this->_convertDiffToTypedArray($value->getOperations());

                foreach ($items as $action => $item) {
                    $items[$action][$attribute] = ArrayHelper::remove($items, $action);
                }

                $newArray = array_merge($newArray, $items);
            }
        }

        return $newArray;
    }

    private function _convertDiffToCount(array $array)
    {
        $newArray = [];

        foreach ($array as $attribute => $value) {
            if ($value instanceof DiffOpAdd) {
                $newArray['add'] = ($newArray['add'] ?? 0) + 1;
            } else if ($value instanceof DiffOpChange) {
                $newArray['change'] = ($newArray['change'] ?? 0) + 1;
            } else if ($value instanceof DiffOpRemove) {
                $newArray['remove'] = ($newArray['remove'] ?? 0) + 1;
            } else if ($value instanceof Diff) {
                $items = $this->_convertDiffToCount($value->getOperations());

                foreach ($items as $action => $item) {
                    $newArray[$action] = ($newArray[$action] ?? 0) + $item;
                }
            }
        }

        return $newArray;
    }

    private function _arrayMultisum(array $arr): float
    {
        $sum = array_sum($arr);

        foreach($arr as $child) {
            $sum += is_array($child) ? $this->_arrayMultisum($child) : 0;
        }

        return $sum;
    }
}
