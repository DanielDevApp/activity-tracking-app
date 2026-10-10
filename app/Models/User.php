<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_MEMBER = 'member';

    protected $fillable = [
        'name', 'email', 'password', 'employee_id', 'phone', 'job_title', 'role', 'is_active', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'role' => self::ROLE_MEMBER,
        'is_active' => true,
        'must_change_password' => false,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /** Stable colour slot (0-7) so a person always gets the same avatar colour. */
    public static function colorIndexFor(?string $name): int
    {
        return crc32(mb_strtolower(trim((string) $name))) % 8;
    }

    public function initials(): string
    {
        return self::initialsFor($this->name);
    }

    public static function initialsFor(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = collect($parts)->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)));

        return $letters->implode('') ?: '?';
    }

    /**
     * Bio details frozen onto every audit record, so reports stay accurate
     * even if the person later changes job title/phone or leaves the team.
     */
    public function bioSnapshot(): array
    {
        return [
            'name' => $this->name,
            'employee_id' => $this->employee_id,
            'job_title' => $this->job_title,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }
}
