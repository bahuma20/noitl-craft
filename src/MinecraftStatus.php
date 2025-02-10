<?php

namespace App;

class MinecraftStatus
{

    /**
     * @param MinecraftState $state
     * @param int $playerCount
     * @param string[] $players usernames of players
     */
    public function __construct(public MinecraftState $state, public int $playerCount, public array $players)
    {
    }
}