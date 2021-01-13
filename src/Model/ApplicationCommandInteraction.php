<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use Amp\Promise;
use iggyvolz\Slashy\Handler\Command;
use iggyvolz\Slashy\Handler\CommandGroup;
use iggyvolz\Slashy\Handler\Registerable;
use ReflectionClass;
use ReflectionNamedType;
use RestCord\Model\Guild\GuildMember;
use RuntimeException;

class ApplicationCommandInteraction extends Interaction
{

    public function __construct(
        int $id,
        int $type,
        int $guild_id,
        int $channel_id,
        GuildMember $member,
        string $token,
        int $version,
        public ApplicationCommandInteractionData $data
    ) {
        parent::__construct(
            $id, $type, $guild_id, $channel_id, $member, $token, $version
        );
    }
    /**
     * @var array<string,class-string<Registerable>>
     */
    private static array $handlers = [];

    private function getHandlerClass(): ?string
    {
        return self::$handlers[$this->guild_id . "|" . $this->data->name] ?? null;
    }

    /**
     * @param string $command
     * @param int $guildId
     * @param class-string<Registerable> $handler
     */
    public static function setHandlerClass(string $command, int $guildId, string $handler): void
    {
        self::$handlers["$guildId|$command"] = $handler;
    }
    public function handle(): Promise
    {
        $handler = self::getHandlerClass();
        if (!is_null($handler)) {
            if (is_subclass_of($handler, CommandGroup::class)) {
                /** @noinspection PhpUnhandledExceptionInspection */
                foreach ((new ReflectionClass($handler))->getProperties() as $rp) {
                    if ($rp->getDeclaringClass()->getName() !== $handler) {
                        continue;
                    }
                    $type = $rp->getType();
                    if (!$type instanceof ReflectionNamedType) {
                        continue;
                    }
                    $type = $type->getName();
                    if (!is_subclass_of($type, Command::class)) {
                        continue;
                    }
                    $option = $this->data->options[0] ?? null;
                    if (is_null($option)) {
                        continue;
                    }
                    if ($type::getName() === $option->name) {
                        return (new $type($this, $option->options ?? []))->handle();
                    }
                }
                throw new RuntimeException("No handler registered");
            } elseif (is_subclass_of($handler, Command::class)) {
                return (new $handler($this, $this->data->options ?? []))->handle();
            } else {
                throw new RuntimeException("No handler registered");
            }
        } else {
            throw new RuntimeException("No handler registered");
        }
    }
}
