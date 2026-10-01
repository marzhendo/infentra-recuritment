<?php

namespace App\Enums;

enum DecisionStatus: string
{
    case Lolos = 'lolos';
    case TidakLolos = 'tidak_lolos';
    case Cadangan = 'cadangan';
}
