<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passkeys\Contracts\PasskeyUser;
use Laravel\Passkeys\PasskeyAuthenticatable;

#[Fillable(['name', 'username', 'is_admin', 'status', 'pin_hash', 'pin_set_at', 'passkey_enrolled_at'])]
#[Hidden(['password', 'pin_hash', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable;

    /**
     * Onboarding states: invited -> pin_set -> active.
     * Only 'active' users may authenticate at all.
     */
    public const STATUS_INVITED = 'invited';

    public const STATUS_PIN_SET = 'pin_set';

    public const STATUS_ACTIVE = 'active';

    /**
     * The store owner's account. This user can never be deleted — the guard
     * lives in Admin\UserController::destroy and the delete control is hidden
     * for them in the users table.
     */
    public const OWNER_USERNAME = 'jeremiah';

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isOwner(): bool
    {
        return $this->username === self::OWNER_USERNAME;
    }

    /**
     * Identifier shown beneath the display name in authenticator / password
     * manager UIs during a passkey ceremony. The package trait falls back to
     * email-then-primary-key, but this app has no email column, so without
     * this override it would surface the raw user id (e.g. "6"). Use the
     * human-readable username instead.
     */
    public function getPasskeyUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'pin_hash' => 'hashed',
            'pin_set_at' => 'datetime',
            'passkey_enrolled_at' => 'datetime',
        ];
    }
}
