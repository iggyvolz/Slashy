<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use Attribute;
use LogicException;
use ReflectionNamedType;
use ReflectionProperty;
use RestCord\Model\Channel\Channel;
use RestCord\Model\Permissions\Role;
use RestCord\Model\User\User;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ApplicationCommandOptionAttribute
{
    /**
     * ApplicationCommandOptionAttribute constructor.
     * @param string $description
     * @param int|null $type
     * @param string|null $name
     * @param bool $default
     * @param list<int|string>|array<string,int|string>|list<ApplicationCommandOptionChoice>|null $choices
     * @param ?ApplicationCommandOption[] $options
     */
    public function __construct(
        public string $description,
        public ?int $type = null,
        public ?string $name = null,
        public bool $default = false,
        public ?array $choices = null,
        public ?array $options = null,
    ) {
    }

    private function getDefaultType(ReflectionProperty $prop): int
    {
        $type = $prop->getType();
        if (is_null($type)) {
            throw new LogicException("Explicit type required for untyped property " . $prop->getDeclaringClass()->getName() . "::" . $prop->getName());
        }
        if (!$type instanceof ReflectionNamedType) {
            throw new LogicException("Single type required for union-typed property " . $prop->getDeclaringClass()->getName() . "::" . $prop->getName());
        }
        if ($type->getName() === "bool" && is_array($this->choices)) {
            return ApplicationCommandOption::TYPE_STRING;
        }
        return match($type->getName()) {
            "int" => ApplicationCommandOption::TYPE_INTEGER,
            "bool" => ApplicationCommandOption::TYPE_BOOLEAN,
            "string" => ApplicationCommandOption::TYPE_STRING,
            Role::class => ApplicationCommandOption::TYPE_ROLE,
            User::class => ApplicationCommandOption::TYPE_USER,
            Channel::class => ApplicationCommandOption::TYPE_CHANNEL,
        default => throw new LogicException("Could not auto-detect type for property " . $prop->getDeclaringClass()->getName() . "::" . $prop->getName())
        };
    }

    public function toOption(ReflectionProperty $property): ApplicationCommandOption
    {
        if (is_null($this->choices)) {
            $choices = null;
        } else {
            $choices = [];
            foreach ($this->choices as $key => $value) {
                if ($value instanceof ApplicationCommandOptionChoice) {
                    $choices[] = $value;
                } elseif (is_int($key)) {
                    $choices[] = new ApplicationCommandOptionChoice(
                        name: strval($value),
                        value: strval($value)
                    );
                } else {
                    $choices[] = new ApplicationCommandOptionChoice(
                        name: $key,
                        value: strval($value)
                    );
                }
            }
        }
        // phpcs:disable
        // -> https://github.com/squizlabs/PHP_CodeSniffer/issues/3159
        return new ApplicationCommandOption(
            type: $this->type ?? $this->getDefaultType($property),
            name: $this->name ?? $property->getName(),
            description: $this->description,
            default: $this->default,
            choices: $choices
        );
        // phpcs:enable
    }
}
