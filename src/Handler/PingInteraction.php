<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Handler;

use Amp\Promise;
use iggyvolz\Slashy\Model\Interaction;
use iggyvolz\Slashy\Model\InteractionResponse;
use RestCord\Model\Guild\GuildMember;
use function Amp\call;
class PingInteraction extends Interaction
{
    public function __construct(
        int $id,
        int $type,
        int $guild_id,
        int $channel_id,
        GuildMember $member,
        string $token,
        int $version
    ) {
        parent::__construct(
            $id, $type, $guild_id, $channel_id, $member, $token, $version
        );
    }
    public function handle(): Promise
    {
        return call(function (): InteractionResponse {
            return InteractionResponse::ping();
        });
    }
}
