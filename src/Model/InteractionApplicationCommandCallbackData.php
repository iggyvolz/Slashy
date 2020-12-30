<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use RestCord\Model\Channel\Embed;

class InteractionApplicationCommandCallbackData
{
    /**
     * InteractionApplicationCommandCallbackData constructor.
     * @param string $content
     * @param bool|null $tts
     * @param Embed[]|null $embeds
     * @param AllowedMentions|null $allowedMentions
     */
    public function __construct(
        public string $content,
        public bool | null $tts = null,
        public array | null $embeds = null,
        public AllowedMentions | null $allowedMentions = null,
    ) {
    }
}
