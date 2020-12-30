<?php

declare(strict_types=1);

namespace iggyvolz\Slashy\Model;

class AllowedMentions
{
    /**
     * AllowedMentions constructor.
     * @param string[] $parse
     * @param int[] $roles
     * @param int[] $users
     * @param bool $replied_user
     */
    public function __construct(
        public array $parse,
        public array $roles,
        public array $users,
        public bool $replied_user = false
    ) {
    }
}
