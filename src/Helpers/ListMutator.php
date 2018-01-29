<?php

namespace Bolt\Helpers;

use Bolt\Collection\MutableBag;

/**
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class ListMutator
{
    /** @var MutableBag */
    private $available;
    /** @var MutableBag */
    private $mutable;

    /**
     * Constructor.
     *
     * @param array $available
     * @param array $mutable
     */
    public function __construct(array $available, array $mutable)
    {
        $this->available = MutableBag::from($available);
        $this->mutable = MutableBag::from($mutable);
    }

    public function __invoke(array $original, array $proposed)
    {
        $isAvailable = function ($k, $v) {
            return $this->available->hasItem($v);
        };
        $isMutable = function ($k, $v) {
            return $this->mutable->hasItem($v);
        };
        $original = MutableBag::from($original)->filter($isAvailable);
        $proposed = MutableBag::from($proposed)->filter($isAvailable)->filter($isMutable);

        $removedFromOriginal = $original->diff($proposed);
        $addedInProposed = $proposed->diff($original);

        // If after post-filtering both arrays match, we're done
        if ($removedFromOriginal->isEmpty() && $addedInProposed->isEmpty()) {
            return $proposed->toArray();
        }

        // Start with values in origin that are also in the proposed array
        $result = $original->intersect($proposed);

        // Re-add selections that are not in the proposed result, but are not mutable
        foreach ($removedFromOriginal as $item) {
            if (!$this->mutable->hasItem($item)) {
                $result->add($item);
            }
        }
        // Add selections from the proposed result that are mutable
        foreach ($addedInProposed as $item) {
            if ($this->mutable->hasItem($item)) {
                $result->add($item);
            }
        }

        return $result->values()->toArray();
    }
}
