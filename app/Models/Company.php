<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['name', 'country', 'city', 'address', 'postal_code', 'phone', 'contact_name', 'contact_email'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
