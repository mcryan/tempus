<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'color',
        'user_id'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeEntries(): BelongsToMany
    {
        return $this->belongsToMany(TimeEntry::class, 'time_entry_tags')
            ->withTimestamps();
    }
}
