<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

class ApplicationCommandInteractionData
{
    /**
     * @var int
     */
    public int $id;
    /**
     * @var string
     */
    public string $name;
    /**
     * @var ApplicationCommandInteractionDataOption[]|null
     */
    public ?array $options = null;
}
