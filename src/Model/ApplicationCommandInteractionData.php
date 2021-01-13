<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use iggyvolz\Slashy\JsonDeserializable;
use iggyvolz\Slashy\Slashy;

class ApplicationCommandInteractionData implements JsonDeserializable
{
    /**
     * @param int $id
     * @param string $name
     * @param ApplicationCommandInteractionDataOption[]|null $options
     */
    public final function __construct(
        public int $id,
        public string $name,
        public ?array $options = null,
    ){}

    public static function fromJson(array $data): static
    {
        $options=Slashy::assertAssocArrayOrNull($data["options"] ?? null);
        return new static(
            id: Slashy::assertInt($data["int"] ?? null),
            name: Slashy::assertString($data["name"] ?? null),
            options: is_null($options) ? null : array_map(/** @param array<string, mixed> $data */fn(array $data): ApplicationCommandInteractionDataOption => ApplicationCommandInteractionDataOption::fromJSON($data), $options)
        );
    }
}
