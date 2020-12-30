<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Handler;

use iggyvolz\Slashy\Model\ApplicationCommandOption;
use ReflectionClass;
use ReflectionNamedType;

abstract class CommandGroup extends Registerable
{
    public static function getOptions(): array
    {
        $options = [];
        $props = (new ReflectionClass(static::class))->getProperties();
        foreach ($props as $property) {
            $type = $property->getType();
            if (!$type instanceof ReflectionNamedType) {
                continue;
            }
            $typeName = $type->getName();
            if (!is_subclass_of($typeName, Command::class)) {
                continue;
            }
            $options[] = new ApplicationCommandOption(
                type: ApplicationCommandOption::TYPE_SUB_COMMAND,
                name: $typeName::getName(),
                description: $typeName::getDescription(),
                options: $typeName::getOptions()
            );
        }
        return $options;
    }
}
