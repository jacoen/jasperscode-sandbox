<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;

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
    ];

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

}
