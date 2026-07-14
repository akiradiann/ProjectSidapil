<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasApiTokens;

    // Role constants
    const ROLE_ADMIN = 'admin';
    const ROLE_FRONT_OFFICE = 'front_office';
    const ROLE_OPERATOR = 'operator';
    const ROLE_CUSTOMER_SERVICE = 'customer_service';
    const ROLE_LOKET = 'loket';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get all available roles
     */
    public static function getRoles(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_FRONT_OFFICE => 'Front Office',
            self::ROLE_OPERATOR => 'Operator',
            self::ROLE_CUSTOMER_SERVICE => 'Customer Service',
            self::ROLE_LOKET => 'Petugas Loket',
        ];
    }

    /**
     * Get role label
     */
    public function getRoleLabelAttribute(): string
    {
        return self::getRoles()[$this->role] ?? $this->role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    /**
     * Check if user is front office
     */
    public function isFrontOffice(): bool
    {
        return $this->role === self::ROLE_FRONT_OFFICE;
    }

    /**
     * Check if user is operator
     */
    public function isOperator(): bool
    {
        return $this->role === self::ROLE_OPERATOR;
    }

    /**
     * Check if user is customer service
     */
    public function isCustomerService(): bool
    {
        return $this->role === self::ROLE_CUSTOMER_SERVICE;
    }

    /**
     * Check if user is loket
     */
    public function isLoket(): bool
    {
        return $this->role === self::ROLE_LOKET;
    }

    /**
     * Check if user can manage users (only admin)
     */
    public function canManageUsers(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Check if user can manage services (admin, front_office, operator, loket)
     */
    public function canManageServices(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_FRONT_OFFICE,
            self::ROLE_OPERATOR,
            self::ROLE_LOKET,
        ]);
    }

    /**
     * Check if user can only view services (customer_service)
     */
    public function canViewServicesOnly(): bool
    {
        return $this->role === self::ROLE_CUSTOMER_SERVICE;
    }

    /**
     * Check if user can manage delivery/pengiriman (admin, customer_service)
     */
    public function canManageDelivery(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_CUSTOMER_SERVICE,
        ]);
    }

    /**
     * Check if user can view delivery (all roles except those who manage)
     */
    public function canViewDelivery(): bool
    {
        return in_array($this->role, [
            self::ROLE_FRONT_OFFICE,
            self::ROLE_OPERATOR,
            self::ROLE_LOKET,
        ]);
    }

    /**
     * Get service requests created by this FO
     */
    public function serviceRequestsAsFo(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'fo_id');
    }

    /**
     * Get service requests processed by this Operator
     */
    public function serviceRequestsAsOperator(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'operator_id');
    }

    /**
     * Get service requests shipped/completed by this CS
     */
    public function serviceRequestsAsCs(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'cs_id');
    }

    /**
     * Get service requests handed over by this Loket
     */
    public function serviceRequestsAsLoket(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'loket_id');
    }

    /**
     * Get total count of handled requests based on current user role
     */
    public function getHandledRequestsCountAttribute(): int
    {
        return match ($this->role) {
            self::ROLE_FRONT_OFFICE => $this->serviceRequestsAsFo()->count(),
            self::ROLE_OPERATOR => $this->serviceRequestsAsOperator()->count(),
            self::ROLE_CUSTOMER_SERVICE => $this->serviceRequestsAsCs()->count(),
            self::ROLE_LOKET => $this->serviceRequestsAsLoket()->count(),
            default => 0,
        };
    }

    /**
     * Determine if the user can access the Filament panel
     * ALL ROLES CAN ACCESS FILAMENT
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Semua role bisa akses Filament
        return true;
    }
}
