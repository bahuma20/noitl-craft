<?php

namespace App;

enum KubernetesState
{
    case STOPPED;
    case RUNNING;
    case STARTING;
    case STOPPING;
}
