<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageResponse extends Model
{
    protected $fillable = ['page_id', 'answers', 'ip'];

    protected $casts = ['answers' => 'array'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
