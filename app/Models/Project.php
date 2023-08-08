<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['manager_id', 'title', 'description', 'due_date', 'status', 'is_pinned'];

    protected $casts = [
        'due_date' => 'date',
        'is_pinned' => 'boolean',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getIsOpenOrPendingAttribute(): bool

    {
        return $this->status === 'open' || $this->status === 'pending';
    }
}
