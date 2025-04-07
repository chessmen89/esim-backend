<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // <-- Import HasApiTokens

class User extends Authenticatable
{
    // Add HasApiTokens here for Sanctum API authentication
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * Added fields required by SRS.
     *
     * @var array<int, string> // Use array<int, string> or list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_number', // Added based on SRS (Optional)
        'country',       // Added based on SRS (Optional)
        'date_of_birth', // Added based on SRS (Required)
        'provider_id',   // For social logins (Optional)
        'provider_name', // For social logins (Optional)
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string> // Use array<int, string> or list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'provider_id', // Usually hidden
    ];

    /**
     * Get the attributes that should be cast.
     *
     * Added cast for date_of_birth.
     * Using the newer casts() method syntax.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date', // <-- Add cast for date
        ];
    }

    // Add relationships here later (e.g., orders, saved packages)
    // public function orders() {
    //     return $this->hasMany(Order::class);
    // }
}
