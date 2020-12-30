<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Handler;

use Amp\Promise;
use iggyvolz\Slashy\Model\Interaction;
use iggyvolz\Slashy\Model\InteractionResponse;

use function Amp\call;

class PingInteraction extends Interaction
{

    public function handle(): Promise
    {
        return call(function (): InteractionResponse {
            return InteractionResponse::ping();
        });
    }
}
