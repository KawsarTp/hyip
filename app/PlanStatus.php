<?php

namespace App;

enum PlanStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Paused = 'paused';
    case Retired = 'retired';
}
