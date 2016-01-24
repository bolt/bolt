<?php

namespace Bolt\Filesystem\Handler;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\Image;

/**
 * Image used for twig where exceptions cannot be caught.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class NullableImage extends Image
{
    /**
     * @inheritDoc
     */
    public function getInfo($cache = true)
    {
        if (!$cache) {
            $this->info = null;
        }
        if (!$this->info) {
            try {
                $this->info = $this->filesystem->getImageInfo($this->path);
            } catch (IOException $e) {
                $this->info = Image\Info::createEmpty();
            }
        }

        return $this->info;
    }
}
