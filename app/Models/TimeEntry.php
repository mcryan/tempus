<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TimeEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'description',
        'start_time',
        'end_time',
        'duration',
        'user_id',
        'project_id',
        'is_billable',
        'billable_amount',
        'pay_amount',
        'is_running',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
        'is_billable' => 'boolean',
        'billable_amount' => 'decimal:2',
        'pay_amount' => 'decimal:2',
        'is_running' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($timeEntry) {
            if ($timeEntry->project_id) {
                $project = Project::find($timeEntry->project_id);
                $timeEntry->is_billable = $project->is_billable;
                
                if ($timeEntry->is_billable) {
                    $rate = $project->billable_rate ?? $timeEntry->user->billable_rate ?? 0;
                    $timeEntry->billable_amount = ($timeEntry->duration / 3600) * $rate;
                    $timeEntry->pay_amount = ($timeEntry->duration / 3600) * ($timeEntry->user->pay_rate ?? 0);
                }
            }
        });

        static::updating(function ($timeEntry) {
            if ($timeEntry->isDirty('duration') && $timeEntry->is_billable) {
                $rate = $timeEntry->project->billable_rate ?? $timeEntry->user->billable_rate ?? 0;
                $timeEntry->billable_amount = ($timeEntry->duration / 3600) * $rate;
                $timeEntry->pay_amount = ($timeEntry->duration / 3600) * ($timeEntry->user->pay_rate ?? 0);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'time_entry_tags');
    }
}
