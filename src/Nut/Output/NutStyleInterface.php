<?php

namespace Bolt\Nut\Output;

use Bolt\Nut\Style\OverwritableStyleInterface;

/**
 * Combines OverwritableOutputInterface & OverwritableStyleInterface.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface NutStyleInterface extends OverwritableOutputInterface, OverwritableStyleInterface
{
}
