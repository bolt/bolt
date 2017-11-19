<?php

namespace Bolt\Storage\Database\Schema;

/**
 * Interface for schema manager.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface SchemaManagerInterface
{
    /**
     * Check to see if we have past the time to re-check our schema.
     *
     * @return bool
     */
    public function isCheckRequired();

    /**
     * Check to see if there is a mismatch in installed versus configured
     * schemas.
     *
     * @return bool
     */
    public function isUpdateRequired();
}
