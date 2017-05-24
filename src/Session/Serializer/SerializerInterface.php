<?php

namespace Bolt\Session\Serializer;

interface SerializerInterface
{
    /**
     * Serializes session data to string.
     *
     * @param array $data Session data
     *
     * @return string Serialized data
     */
    public function serialize($data);

    /**
     * Un-serializes session data from string.
     *
     * @param string $data Serialized data
     *
     * @throws \RuntimeException If un-serialization fails
     *
     * @return array Session data
     */
    public function unserialize($data);
}
