<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

use Amp\Promise;

class InteractionContainer
{
    /**
     * @var Interaction
     */
    public Interaction $interaction;

    /**
     * @return Promise<InteractionResponse>
     */
    public function handle(): Promise
    {
        return $this->interaction->handle();
    }
}
