<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['manager_id', 'title', 'description', 'due_date'];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
