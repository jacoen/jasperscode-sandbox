<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_token',
        'token_expires_at',
        'password_changed_at',
        'two_factor_enabled',
        'two_factor_code',
        'two_factor_expires_at',
        'locked_until',
        'two_factor_attempts',
        'last_attempt_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'password_token',
        'token_expires_at',
        'password_changed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'two_factor_expires_at' => 'datetime',
        'two_factor_enabled' => 'boolean',
        'locked_until' => 'datetime',
        'last_attempt_at' => 'datetime',
    ];

    public function projects()
    {
        return $this->hasMany(Project::class, 'manager_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'author_id');
    }

    public function generatePasswordToken()
    {
        $this->timestamps = false;
        $this->password_token = Str::random(32);
        $this->token_expires_at = now()->addHour();
        $this->save();
    }

    public function getHasTokenExpiredAttribute()
    {
        return ! empty($this->token_expires_at) && $this->token_expires_at->lt(now());
    }

    public function getHasChangedPasswordAttribute()
    {
        return $this->password_changed_at !== null;
    }

    public function generateTwoFactorCode(): void
    {
        $this->timestamps = false;
        $this->two_factor_code = generateDigitCode();
        $this->two_factor_expires_at = now()->addMinutes(5);
        $this->save();
        $this->timestamps = true;
    }

    public function resetTwoFactorCode(): void
    {
        $this->timestamps = false;
        $this->two_factor_code = null;
        $this->two_factor_expires_at = null;
        $this->save();
        $this->timestamps = true;
    }

    public function lockUser(): void
    {
        $this->timestamps = false;
        $this->locked_until = now()->addMinutes(10);
        $this->save();
        $this->timestamps = true;
    }
}
