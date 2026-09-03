<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAuthSetting extends Model
{
    protected $fillable = ['provider', 'client_id', 'client_secret', 'redirect_url', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'client_secret' => 'encrypted',
            'is_enabled' => 'boolean',
        ];
    }
}
