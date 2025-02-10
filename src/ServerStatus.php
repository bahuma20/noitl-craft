<?php

namespace App;

class ServerStatus
{

    /**
     * @param ServerState $state
     * @param int $playerCount
     * @param string[] $players Usernames of online players
     */
    public function __construct(public ServerState $state, public int $playerCount, public array $players)
    {
    }
}