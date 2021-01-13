<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use Amp\Promise;
use iggyvolz\Slashy\Handler\PingInteraction;
use iggyvolz\Slashy\JsonDeserializable;
use iggyvolz\Slashy\Slashy;
use RestCord\Model\Guild\GuildMember;
use RuntimeException;
abstract class Interaction implements JsonDeserializable
{
    public function __construct(
        public int $id,
        public int $type,
        public int $guild_id,
        public int $channel_id,
        public GuildMember $member,
        public string $token,
        public int $version
    ) {}

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
    public static function fromJson(array $data): static
    {
        if(static::class !== self::class) {
            throw new \LogicException("Cannot be called from subclass");
        }
        $id=Slashy::assertInt($data["int"] ?? null);
        $type=Slashy::assertInt($data["type"] ?? null);
        $guild_id=Slashy::assertInt($data["guild_id"] ?? null);
        $channel_id=Slashy::assertInt($data["channel_id"] ?? null);
        $member=new GuildMember(Slashy::assertAssocArray($data["member"] ?? null));
        $token=Slashy::assertString($data["token"] ?? null);
        $version=Slashy::assertInt($data["version"] ?? null);
        /**
         * @var static $self
         */
        $self= match($type) {
            self::TYPE_PING => new PingInteraction(
                id: $id,
                type: $type,
                guild_id: $guild_id,
                channel_id: $channel_id,
                member: $member,
                token: $token,
                version: $version
            ),
            self::TYPE_APPLICATION_COMMAND => new ApplicationCommandInteraction(
                id: $id,
                type: $type,
                guild_id: $guild_id,
                channel_id: $channel_id,
                member: $member,
                token: $token,
                version: $version,
                data: ApplicationCommandInteractionData::fromJSON(Slashy::assertAssocArray($data["data"] ?? null))
            ),
        };
        return $self;
    }
}
