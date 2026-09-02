<?php

namespace App;

enum UserRole: string
{
    case User = 'user';
    case Admin = 'admin';
    case Finance = 'finance';
    case Compliance = 'compliance';
    case Support = 'support';
}
