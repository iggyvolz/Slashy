<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use iggyvolz\Slashy\JsonDeserializable;
use iggyvolz\Slashy\Slashy;

class ApplicationCommandInteractionDataOption implements JsonDeserializable
{
    /**
     * @param string $name
     * @param int|string|bool|null $value
     * @param ApplicationCommandInteractionDataOption[]|null $options
     */
    public final function __construct(
        public string $name,
        public int|string|bool|null $value = null,
        public ?array $options = null,
    ) {}

    public static function fromJson(array $data): static
    {
        $options=Slashy::assertAssocArrayOrNull($data["options"] ?? null);
        return new static(
            name: Slashy::assertString($data),
            value: Slashy::assertIntStringBoolNull($data),
            options: is_null($options) ? null : array_map(/** @param array<string, mixed> $data */fn(array $data): ApplicationCommandInteractionDataOption => ApplicationCommandInteractionDataOption::fromJSON($data), $options)
        );
    }
}
