<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

class ApplicationCommandOptionChoice
{
    public function __construct(
        public string $name,
        public string | int $value
    ) {
    }
}
