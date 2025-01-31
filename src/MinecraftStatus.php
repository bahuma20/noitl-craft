<?php

namespace App;

class MinecraftStatus
{

    public function __construct(public MinecraftState $state, public int $playerCount)
    {
    }
}