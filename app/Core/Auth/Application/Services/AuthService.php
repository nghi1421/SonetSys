<?php

declare(strict_types=1);

namespace App\Core\Auth\Application\Services;

use App\Core\Auth\Application\Contracts\UserRepositoryInterface;
use App\Core\Auth\Application\DTOs\ForgotPasswordData;
use App\Core\Auth\Application\DTOs\LoginData;
use App\Core\Auth\Application\DTOs\RegisterUserData;
use App\Core\Auth\Application\DTOs\ResetPasswordData;
use App\Core\Auth\Domain\Enums\RoleSlug;
use App\Core\Auth\Domain\Enums\UserStatus;
use App\Core\Auth\Domain\Models\Role;
use App\Core\Auth\Domain\Models\User;
use App\Modules\Subscription\Application\Services\SubscriptionService;
use App\Modules\Subscription\Domain\Enums\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * @return array{user: User, token: string}
     */
    public function register(RegisterUserData $data): array
    {
        $role = Role::query()->where('slug', RoleSlug::User->value)->firstOrFail();

        return DB::transaction(function () use ($data, $role): array {
            $user = $this->users->create([
                'role_id' => $role->id,
                'name' => $data->name,
                'email' => $data->email,
                'password' => Hash::make($data->password),
                'status' => UserStatus::Active,
            ]);

            $this->subscriptions->subscribe((int) $user->id, SubscriptionPlan::Free);

            return [
                'user' => $user,
                'token' => $user->createToken('api')->plainTextToken,
            ];
        });
    }

    /**
     * @return array{user: User, token: string}
     */
    public function login(LoginData $data): array
    {
        $user = $this->users->findByEmail($data->email);

        if (! $user || ! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            throw ValidationException::withMessages([
                'email' => ['This account is not active.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }

    /**
     * Always succeeds from the caller's perspective regardless of whether
     * the email is registered, and always pays the same hashing cost either
     * way, so neither the response nor its timing can be used to enumerate
     * valid accounts.
     */
    public function sendPasswordResetLink(ForgotPasswordData $data): void
    {
        $user = $this->users->findByEmail($data->email);

        $token = Str::random(64);
        $hashedToken = Hash::make($token);

        if ($user === null) {
            return;
        }

        DB::table('user_password_reset_tokens')->updateOrInsert(
            ['user_id' => $user->id],
            ['token' => $hashedToken, 'created_at' => now()],
        );

        $user->sendPasswordResetNotification($token);
    }

    public function resetPassword(ResetPasswordData $data): void
    {
        $user = $this->users->findByEmail($data->email);
        $record = $user !== null
            ? DB::table('user_password_reset_tokens')->where('user_id', $user->id)->first()
            : null;

        $expiryMinutes = (int) config('auth.passwords.users.expire');

        $isValid = $user !== null
            && $record !== null
            && Hash::check($data->token, $record->token)
            && abs(now()->diffInMinutes($record->created_at)) <= $expiryMinutes;

        if (! $isValid) {
            throw ValidationException::withMessages([
                'email' => ['This reset link is invalid or has expired.'],
            ]);
        }

        DB::transaction(function () use ($user, $data): void {
            $user->forceFill(['password' => Hash::make($data->password)])->save();

            // A leaked/stolen token shouldn't survive a password reset.
            $user->tokens()->delete();

            DB::table('user_password_reset_tokens')->where('user_id', $user->id)->delete();
        });
    }
}
