<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use JetBrains\PhpStorm\Pure;

class InteractionResponse
{
    public const TYPE_PONG = 1;
    public const TYPE_ACKNOWLEDGE = 2;
    public const TYPE_CHANNEL_MESSAGE = 3;
    public const TYPE_CHANNEL_MESSAGE_WITH_SOURCE = 4;
    public const TYPE_ACKNOWLEDGE_WITH_SOURCE = 5;
    public function __construct(
        public int $type,
        public InteractionApplicationCommandCallbackData | null $data,
    ) {
    }

    #[Pure]
    public static function ping(): self
    {
        return new self(self::TYPE_PONG, null);
    }

    #[Pure]
    public static function response(InteractionApplicationCommandCallbackData | string | null $data, bool $showSource = false): self
    {
        if (is_string($data)) {
            $data = new InteractionApplicationCommandCallbackData($data);
        }
        return new self(match($showSource) {
            true => match(is_null($data)) {
                true => self::TYPE_ACKNOWLEDGE_WITH_SOURCE,
                false => self::TYPE_CHANNEL_MESSAGE_WITH_SOURCE,
            },
            false => match(is_null($data)) {
                true => self::TYPE_ACKNOWLEDGE,
                false => self::TYPE_CHANNEL_MESSAGE,
            },
        }, $data);
    }
}
