<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Handler;

use Amp\Promise;
use iggyvolz\Slashy\Model\ApplicationCommandInteraction;
use iggyvolz\Slashy\Model\ApplicationCommandInteractionDataOption;
use iggyvolz\Slashy\Model\ApplicationCommandOptionAttribute;
use iggyvolz\Slashy\Model\InteractionResponse;
use LogicException;
use ReflectionAttribute;
use ReflectionNamedType;
use RestCord\DiscordClient;
use RestCord\Model\Channel\Channel;
use RestCord\Model\Guild\Guild;
use RestCord\Model\Guild\GuildMember;
use RestCord\Model\Permissions\Role;
use RestCord\Model\User\User;
use RuntimeException;

abstract class Command extends Registerable
{
    protected Guild $guild;

    protected GuildMember $member;

    /**
     * Command constructor.
     * @param DiscordClient $restcord
     * @param ApplicationCommandInteraction $interaction
     * @param ApplicationCommandInteractionDataOption[] $options
     */
    final public function __construct(
        protected DiscordClient $restcord,
        private ApplicationCommandInteraction $interaction,
        private array $options,
    ) {
        parent::__construct();
        $optionVals = [];
        foreach ($options as $option) {
            $optionVals[$option->name] = $option->value;
        }
        $this->guild = $this->restcord->guild->getGuild(["guild.id" => $this->interaction->guild_id]);
        $this->member = $this->interaction->member;
        foreach (static::getOptionProperties(ApplicationCommandOptionAttribute::class) as $i => $prop) {
            $prop->setAccessible(true);
            /**
             * @var ApplicationCommandOptionAttribute $attribute
             */
            $attribute = $prop->getAttributes(ApplicationCommandOptionAttribute::class, ReflectionAttribute::IS_INSTANCEOF)[0]->newInstance();
            $option = $attribute->toOption($prop);
            $type = $prop->getType();
            if (!$type instanceof ReflectionNamedType) {
                throw new LogicException("Union types not supported");
            }
            if (!array_key_exists($option->name, $options)) {
                if (!$prop->hasDefaultValue()) {
                    throw new RuntimeException("No value specified for " . $option->name);
                }
            } else {
                $val = $optionVals[$option->name];
                if (get_debug_type($val) === $type->getName()) {
                    $prop->setValue($this, $val);
                } elseif ($type->getName() === "bool" && is_array($option->choices)) {
                    if ($val === $option->choices[0]->name) {
                        $prop->setValue($this, true);
                    } elseif ($val === $option->choices[1]->name) {
                        $prop->setValue($this, false);
                    } else {
                        throw new RuntimeException("Could not match $val to " . $option->choices[0]->name . " or " . $option->choices[1]->name . " for " . $option->name);
                    }
                } elseif ($type->getName() === Role::class) {
                    $roleId = intval($val);
                    $roles = $this->restcord->guild->getGuildRoles(["guild.id" => $this->interaction->guild_id]);
                    $role = array_values(array_filter($roles, fn(Role $r): bool => $r->id === $roleId))[0] ?? null;
                    if (is_null($role)) {
                        throw new RuntimeException("Could not find role for " . $option->name);
                    }
                    $prop->setValue($this, $role);
                } elseif ($type->getName() === User::class) {
                    $userId = intval($val);
                    $user = $this->restcord->user->getUser(["user.id" => $userId]);
                    $prop->setValue($this, $user);
                } elseif ($type->getName() === Channel::class) {
                    $channelId = intval($val);
                    $channel = $this->restcord->channel->getChannel(["channel.id" => $channelId]);
                    $prop->setValue($this, $channel);
                } else {
                    throw new RuntimeException("Could not resolve value for " . $option->name . ": given type " . get_debug_type($val) . ", wanted " . $type->getName());
                }
            }
        }
    }

    public function isAdmin(): bool
    {
        // Check if user is server owner
        if ($this->guild->owner_id === $this->member->user->id) {
            return true;
        }
        // Check if user has a role that grants them Admin
        foreach ($this->restcord->guild->getGuildRoles(["guild.id" => $this->guild->id]) as $role) {
            if (in_array($role->id, $this->member->roles) && ($role->permissions & 0x8)) { // 0x8 = administrator
                return true;
            }
        }
        return false;
    }



    /**
     * @return Promise<InteractionResponse>
     */
    abstract public function handle(): Promise;
}
