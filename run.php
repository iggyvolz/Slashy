<?php

use iggyvolz\Slashy\Slashy;

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/config.php";
(new Slashy(
    clientID: CLIENT_ID,
    publicKey: PUBLIC_KEY,
    botToken: BOT_TOKEN,
    address: ADDRESS,
    commands: COMMANDS,
    guilds: GUILDS,
))->run();