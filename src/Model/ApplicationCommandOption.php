<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

class ApplicationCommandOption
{
    public const TYPE_SUB_COMMAND = 1;
    public const TYPE_SUB_COMMAND_GROUP = 2;
    public const TYPE_STRING = 3;
    public const TYPE_INTEGER = 4;
    public const TYPE_BOOLEAN = 5;
    public const TYPE_USER = 6;
    public const TYPE_CHANNEL = 7;
    public const TYPE_ROLE = 8;
    /**
     * @param int $type
     * @param string $name
     * @param string $description
     * @param bool $default
     * @param bool $required
     * @param ?ApplicationCommandOptionChoice[] $choices
     * @param ?ApplicationCommandOption[] $options
     */
    public function __construct(
        public int $type,
        public string $name,
        public string $description,
        public bool $default = false,
        public bool $required = false,
        public ?array $choices = null,
        public ?array $options = null,
    ) {
    }
}
