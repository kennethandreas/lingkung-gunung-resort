<?php

namespace App\Models;

use App\Notifications\Customer\ResetPasswordNotification;
use App\Notifications\Customer\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun pelanggan website (guard "customer"). Terpisah dari staf (User) dan
 * dari Customer (data pemesan per booking). Kepemilikan booking lewat bookings.customer_account_id.
 */
#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class CustomerAccount extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function savedUnitTypes(): BelongsToMany
    {
        return $this->belongsToMany(UnitType::class, 'saved_unit_types')->withTimestamps();
    }

    /** Apakah verifikasi email sedang diwajibkan (CUSTOMER_EMAIL_VERIFICATION). */
    public static function verificationRequired(): bool
    {
        return (bool) config('resort.customer_email_verification');
    }

    /** Saat verifikasi dimatikan, semua akun dianggap terverifikasi (fitur akun langsung terbuka). */
    public function hasVerifiedEmail(): bool
    {
        return ! self::verificationRequired() || $this->email_verified_at !== null;
    }

    public function sendEmailVerificationNotification(): void
    {
        if (self::verificationRequired()) {
            $this->notify(new VerifyEmailNotification);
        }
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
