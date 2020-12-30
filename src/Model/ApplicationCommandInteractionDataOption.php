<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

class ApplicationCommandInteractionDataOption
{
    /**
     * @var string
     */
    public string $name;
    /**
     * @var int|string|bool|null
     */
    public int | string | bool | null $value = null;
    /**
     * @var ApplicationCommandInteractionDataOption[]|null
     */
    public ?array $options = null;
}
