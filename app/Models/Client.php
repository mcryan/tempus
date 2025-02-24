<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'is_billable_by_default',
        'default_billable_rate',
        'notes',
    ];

    protected $casts = [
        'is_billable_by_default' => 'boolean',
        'default_billable_rate' => 'decimal:2',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class);
    }
}
