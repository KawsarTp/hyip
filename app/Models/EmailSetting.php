<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $fillable = ['mailer', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name', 'reply_to', 'is_enabled'];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'password' => 'encrypted',
            'is_enabled' => 'boolean',
        ];
    }
}
