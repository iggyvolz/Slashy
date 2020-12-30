<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use Amp\Promise;
use iggyvolz\Slashy\Handler\PingInteraction;
use RestCord\Model\Guild\GuildMember;
use RuntimeException;

abstract class Interaction
{
    /**
     * @var int
     */
    public int $id;
    /**
     * @var int
     */
    public int $type;
    /**
     * @var int
     */
    public int $guild_id;
    /**
     * @var int
     */
    public int $channel_id;
    /**
     * @var GuildMember
     */
    public GuildMember $member;
    /**
     * @var string
     */
    public string $token;
    /**
     * @var int
     */
    public int $version;

    public const TYPE_PING = 1;
    public const TYPE_APPLICATION_COMMAND = 2;

    /**
     * @return Promise<InteractionResponse>
     */
    abstract public function handle(): Promise;

    /**
     * @param array<string|int,mixed> $data
     * @return string
     */
    public static function getInteractionType(array $data): string
    {
        if (!is_int($data["type"] ?? null)) {
            throw new RuntimeException("Invalid interaction type");
        }
        return match($data["type"]) {
            self::TYPE_PING => PingInteraction::class,
            self::TYPE_APPLICATION_COMMAND => ApplicationCommandInteraction::class,
        default => throw new RuntimeException("Invalid interaction type"),
        };
    }
}
