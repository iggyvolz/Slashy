<?php


namespace iggyvolz\Slashy;


interface JsonDeserializable
{
    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromJson(array $data): static;
}