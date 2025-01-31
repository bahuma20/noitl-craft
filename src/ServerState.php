<?php

namespace App;

enum ServerState: string
{
    case STOPPED = 'STOPPED';
    case STARTING = 'STARTING';
    case RUNNING = 'RUNNING';
}
