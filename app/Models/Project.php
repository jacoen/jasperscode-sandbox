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

    protected $fillable = ['manager_id', 'title', 'description', 'due_date'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public static function booted()
    {
        static::deleted(function (Project $project) {
            $project->tasks()->each( function ($task) {
                $task->delete();  
            });
        });
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getLastUpdatedAttribute(): string
    {
        if ($this->updated_at->gt(now()->subDay())) {
            return $this->updated_at->diffForHumans();
        }

        return $this->updated_at->format('d M Y H:m');
    }
}
