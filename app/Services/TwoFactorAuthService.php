<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Cache;

class TwoFactorAuthService
{
    /**
     * The Google2FA instance.
     */
    private Google2FA $google2fa;

    /**
     * Create a new TwoFactorAuthService instance.
     */
    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate a secret key for 2FA.
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * Get QR code URL for the authenticator app.
     */
    public function getQrCodeUrl(User $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );
    }

    /**
     * Generate QR code SVG for the authenticator app.
     */
    public function generateQrCode(User $user, string $secret): string
    {
        $qrCodeUrl = $this->getQrCodeUrl($user, $secret);
        return QrCode::size(200)->generate($qrCodeUrl);
    }

    /**
     * Enable 2FA for a user.
     */
    public function enable2FA(User $user, string $secret, string $confirmationCode): bool
    {
        // Verify the confirmation code first
        if (!$this->verify($user, $confirmationCode, $secret)) {
            return false;
        }

        // Generate recovery codes
        $recoveryCodes = $this->generateRecoveryCodes();

        // Enable 2FA
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes))
        ]);

        // Clear any cached verification attempts
        $this->clearVerificationAttempts($user);

        return true;
    }

    /**
     * Disable 2FA for a user.
     */
    public function disable2FA(User $user): void
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        // Clear any cached verification attempts
        $this->clearVerificationAttempts($user);
    }

    /**
     * Verify a 2FA code for a user.
     */
    public function verify(User $user, string $code, string $secret = null): bool
    {
        if (!$user->two_factor_enabled && !$secret) {
            return false;
        }

        // Rate limiting - prevent brute force attacks
        if ($this->tooManyVerificationAttempts($user)) {
            return false;
        }

        $secretToUse = $secret ?: Crypt::decryptString($user->two_factor_secret);
        
        $isValid = $this->google2fa->verifyKey($secretToUse, $code, 2); // 2 = window tolerance

        if ($isValid) {
            $this->clearVerificationAttempts($user);
        } else {
            $this->incrementVerificationAttempts($user);
        }

        return $isValid;
    }

    /**
     * Verify a recovery code for a user.
     */
    public function verifyRecoveryCode(User $user, string $code): bool
    {
        if (!$user->two_factor_recovery_codes) {
            return false;
        }

        // Rate limiting
        if ($this->tooManyVerificationAttempts($user)) {
            return false;
        }

        $recoveryCodes = json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        );

        if (in_array($code, $recoveryCodes)) {
            // Remove used recovery code
            $remainingCodes = array_diff($recoveryCodes, [$code]);
            $user->update([
                'two_factor_recovery_codes' => Crypt::encryptString(json_encode(array_values($remainingCodes)))
            ]);
            
            $this->clearVerificationAttempts($user);
            return true;
        }

        $this->incrementVerificationAttempts($user);
        return false;
    }

    /**
     * Generate new recovery codes for a user.
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        if (!$user->two_factor_enabled) {
            throw new \InvalidArgumentException('2FA must be enabled to regenerate recovery codes.');
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        
        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes))
        ]);

        return $recoveryCodes;
    }

    /**
     * Get recovery codes for a user (decrypted).
     */
    public function getRecoveryCodes(User $user): array
    {
        if (!$user->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(
            Crypt::decryptString($user->two_factor_recovery_codes),
            true
        ) ?: [];
    }

    /**
     * Check if a user has recovery codes available.
     */
    public function hasRecoveryCodes(User $user): bool
    {
        return count($this->getRecoveryCodes($user)) > 0;
    }

    /**
     * Get 2FA status for a user.
     */
    public function getStatus(User $user): array
    {
        return [
            'enabled' => $user->two_factor_enabled,
            'confirmed' => !is_null($user->two_factor_confirmed_at),
            'recovery_codes_count' => count($this->getRecoveryCodes($user)),
            'has_secret' => !is_null($user->two_factor_secret),
            'enabled_at' => $user->two_factor_confirmed_at,
        ];
    }

    /**
     * Generate backup codes for 2FA recovery.
     */
    private function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(
                substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4) . '-' .
                substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4)
            );
        }
        return $codes;
    }

    /**
     * Check if there are too many verification attempts.
     */
    private function tooManyVerificationAttempts(User $user): bool
    {
        $key = $this->getVerificationAttemptsKey($user);
        $attempts = Cache::get($key, 0);
        
        return $attempts >= 5; // Max 5 attempts per hour
    }

    /**
     * Increment verification attempts for rate limiting.
     */
    private function incrementVerificationAttempts(User $user): void
    {
        $key = $this->getVerificationAttemptsKey($user);
        $attempts = Cache::get($key, 0);
        
        Cache::put($key, $attempts + 1, 3600); // 1 hour
    }

    /**
     * Clear verification attempts.
     */
    private function clearVerificationAttempts(User $user): void
    {
        $key = $this->getVerificationAttemptsKey($user);
        Cache::forget($key);
    }

    /**
     * Get the cache key for verification attempts.
     */
    private function getVerificationAttemptsKey(User $user): string
    {
        return "2fa_attempts:{$user->id}:" . now()->format('Y-m-d-H');
    }

    /**
     * Generate a temporary 2FA bypass code for emergency access.
     */
    public function generateBypassCode(User $user, int $validForMinutes = 30): string
    {
        $bypassCode = Str::random(32);
        
        Cache::put(
            "2fa_bypass:{$user->id}:{$bypassCode}",
            true,
            $validForMinutes * 60
        );

        return $bypassCode;
    }

    /**
     * Verify a 2FA bypass code.
     */
    public function verifyBypassCode(User $user, string $bypassCode): bool
    {
        $key = "2fa_bypass:{$user->id}:{$bypassCode}";
        
        if (Cache::has($key)) {
            Cache::forget($key);
            return true;
        }

        return false;
    }

    /**
     * Check if 2FA is required for the user based on their role.
     */
    public function is2FARequired(User $user): bool
    {
        // 2FA is required for admins and club admins
        if ($user->hasAnyRole(['admin', 'club_admin'])) {
            return true;
        }

        // Check if club requires 2FA
        foreach ($user->clubs as $club) {
            if ($club->pivot->role === 'admin' && $club->settings['require_2fa'] ?? false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get 2FA setup URL for mobile apps.
     */
    public function getMobileSetupUrl(User $user, string $secret): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s',
            urlencode(config('app.name')),
            urlencode($user->email),
            $secret,
            urlencode(config('app.name'))
        );
    }

    /**
     * Get statistics about 2FA usage.
     */
    public function getUsageStatistics(): array
    {
        $totalUsers = User::count();
        $enabled2FA = User::where('two_factor_enabled', true)->count();
        $confirmed2FA = User::whereNotNull('two_factor_confirmed_at')->count();
        
        return [
            'total_users' => $totalUsers,
            'enabled_2fa' => $enabled2FA,
            'confirmed_2fa' => $confirmed2FA,
            'enabled_percentage' => $totalUsers > 0 ? round(($enabled2FA / $totalUsers) * 100, 2) : 0,
            'confirmed_percentage' => $totalUsers > 0 ? round(($confirmed2FA / $totalUsers) * 100, 2) : 0,
        ];
    }
}