<?php
namespace Bolt\Session\Serializer;

interface SerializerInterface
{
    /**
     * Serializes session data to string
     *
     * @param array $data Session data
     *
     * @return string Serialized data
     */
    public function serialize($data);

    /**
     * Unserializes session data from string
     *
     * @param string $data Serialized data
     *
     * @throws \RuntimeException If unserialization fails
     *
     * @return array Session data
     */
    public function unserialize($data);
}
