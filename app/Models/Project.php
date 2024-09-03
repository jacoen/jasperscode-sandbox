<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Project extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['company_id', 'manager_id', 'title', 'description', 'due_date', 'status', 'is_pinned'];

    protected $casts = [
        'due_date' => 'date',
        'is_pinned' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

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

    public function getDueDateAlertAttribute(): bool
    {
        return $this->due_date->lte(now()->addWeek()) && $this->getIsOpenOrPendingAttribute();
    }

    public function getDueDateWarningAttribute(): bool
    {
        return $this->due_date->lte(now()->addMonth()) && $this->getIsOpenOrPendingAttribute() && ! $this->getDueDateAlertAttribute();
    }

    public function getDueDateDifferenceAttribute(): string
    {
        if ($this->due_date->lte(now()->addDay())) {
            return 'tomorrow.';
        }

        return 'in '.$this->due_date->diffInDays(now()->toDateString()).' days.';
    }

    public function scopeSearch($query, $search)
    {
        return $query->when($search, function ($query) use ($search) {
            return $query->where('title', 'LIKE', '%'.$search.'%');  
        });
    }

    public function scopeFilterStatus($query, $status)
    {
        return $query->when($status, function ($query) use ($status) {
            $query->where('status', $status);

            if ($status !== 'completed') {
                $query->where('due_date', '>=', now()->startOfDay());
            }
        });
    }

    public function scopeDefaultFilter($query)
    {
        return $query->where('due_date', '>=', now()->startOfDay())
            ->orWhere('status', 'completed');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['manager.name', 'title', 'description', 'due_date', 'status', 'is_pinned'])
            ->logOnlyDirty()
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }
}
