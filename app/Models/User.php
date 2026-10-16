<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_VHS_ADMIN = 'vhs_admin';

    public const ROLE_VHS_OPERATOR = 'vhs_operator';

    public const ROLE_SUPERVISOR = 'supervisor';

    public const ROLE_CUSTOMER_VIEWER = 'customer_viewer';

    public const ROLE_CUSTOMER_REQUESTER = 'customer_requester';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'role',
        'is_active',
        'password',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_VHS_ADMIN => 'VHS Admin',
            self::ROLE_VHS_OPERATOR => 'VHS Operator',
            self::ROLE_SUPERVISOR => 'Supervisor',
            self::ROLE_CUSTOMER_VIEWER => 'Customer Viewer',
            self::ROLE_CUSTOMER_REQUESTER => 'Customer Requester',
        ];
    }

    public function roleLabel(): string
    {
        return self::roleLabels()[$this->role] ?? $this->role;
    }

    /**
     * @param  string|array<int, string>  $roles
     */
    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    public function canOverrideOperationalControl(): bool
    {
        return $this->hasRole([self::ROLE_SUPER_ADMIN, self::ROLE_SUPERVISOR]);
    }

    public function canWriteTransactions(): bool
    {
        return $this->hasRole([
            self::ROLE_SUPER_ADMIN,
            self::ROLE_VHS_ADMIN,
            self::ROLE_VHS_OPERATOR,
            self::ROLE_SUPERVISOR,
        ]);
    }

    public function canCreateFuelOrders(): bool
    {
        return $this->hasRole([
            self::ROLE_SUPER_ADMIN,
            self::ROLE_VHS_ADMIN,
            self::ROLE_SUPERVISOR,
            self::ROLE_CUSTOMER_REQUESTER,
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
