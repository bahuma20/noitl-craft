<?php

namespace App;

enum ServerState
{
    case STOPPED;
    case STARTING;
    case RUNNING;
}
