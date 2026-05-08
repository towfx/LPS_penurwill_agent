<?php

namespace App\Services;

use App\Exceptions\VerificationDailyLimitException;
use App\Mail\EmailVerificationCode;
use App\Models\RegistrationVerification;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Mail;

/**
 * Manages 6-digit email verification codes for the registration wizard.
 * Daily attempt counting spans all code rows for a given email created today.
 */
class RegistrationVerificationService
{
    /**
     * Generate a new 6-digit code, expiring in 15 minutes, and send it.
     */
    public function generate(string $email): RegistrationVerification
    {
        $verification = RegistrationVerification::create([
            'email' => $email,
            'code' => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(15),
            'attempts' => 0,
            'verified' => false,
        ]);

        Mail::to($email)->send(new EmailVerificationCode($verification));

        return $verification;
    }

    /**
     * Verify a code — returns true on success, false on wrong/expired/exhausted.
     * Increments per-code attempts on every wrong guess.
     */
    public function verify(string $email, string $code): bool
    {
        $verification = RegistrationVerification::where('email', $email)
            ->where('verified', false)
            ->latest()
            ->first();

        if (! $verification) {
            return false;
        }

        if ($verification->isExpired() || $verification->isExhausted()) {
            return false;
        }

        if ($verification->code !== $code) {
            $verification->increment('attempts');

            return false;
        }

        $verification->markVerified();

        return true;
    }

    /**
     * Invalidate any existing unverified code for the email and generate a new one.
     * Enforces the daily attempt limit from SystemSetting.
     *
     * @throws VerificationDailyLimitException
     */
    public function resend(string $email): RegistrationVerification
    {
        if ($this->isExhausted($email)) {
            throw new VerificationDailyLimitException(
                'Daily verification attempt limit reached. Please try again tomorrow.'
            );
        }

        // Invalidate current unverified codes
        RegistrationVerification::where('email', $email)
            ->where('verified', false)
            ->update(['attempts' => 99]); // mark exhausted so they cannot be used

        return $this->generate($email);
    }

    /**
     * Whether the daily attempt total for this email has hit the system limit.
     */
    public function isExhausted(string $email): bool
    {
        return $this->getDailyAttemptCount($email) >= $this->dailyLimit();
    }

    /**
     * Total attempts recorded for this email today, across all code rows.
     */
    public function getDailyAttemptCount(string $email): int
    {
        return (int) RegistrationVerification::where('email', $email)
            ->whereDate('created_at', now()->toDateString())
            ->sum('attempts');
    }

    protected function dailyLimit(): int
    {
        return (int) (SystemSetting::first()?->email_verification_max_retry ?? 10);
    }
}
