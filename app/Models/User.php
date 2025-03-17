<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 *
 */
class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /**
     * Admin position.
     */
    public const POSITION_ADMIN = 1;

    /**
     * User position.
     */
    public const POSITION_USER = 2;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'position_id',
        'photo',
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
    ];

    /**
     * @return string[]
     */
    public static function getPositions(): array
    {
        return [
            self::POSITION_ADMIN => 'admin',
            self::POSITION_USER => 'user',
        ];
    }

    /**
     * @param $id
     * @return string
     */
    public static function getPosition($id): string
    {
        $positions = self::getPositions();
        return $positions[$id] ?? 'Unknown';
    }
}
