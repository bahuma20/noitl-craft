<?php

namespace App;

class ServerStatus
{

    public function __construct(public ServerState $state, public int $playerCount)
    {
    }
}