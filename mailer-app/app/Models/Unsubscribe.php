<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unsubscribe extends Model
{
    protected $fillable = ['email', 'reason'];

    public static function isUnsubscribed(string $email): bool
    {
        return static::query()->where('email', mb_strtolower(trim($email)))->exists();
    }
}
