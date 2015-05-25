<?php
namespace Bolt\Logger;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * @author Carson Full <carsonfull@gmail.com>
 */
interface FlashBagAttachableInterface
{
    /**
     * Attach a FlashBag.
     *
     * @param FlashBagInterface $flashBag
     */
    public function attachFlashBag(FlashBagInterface $flashBag);

    /**
     * Returns whether a FlashBag has been attached.
     *
     * @return boolean
     */
    public function isFlashBagAttached();
}
