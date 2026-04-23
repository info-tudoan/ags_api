<?php

namespace App\Services;

use PragmaRX\Google2FA\Google2FA;

class MfaService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    public function getQRCodeUrl(string $email, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl('GPS Attendance', $email, $secret);
    }

    public function verifyToken(string $secret, string $token): bool
    {
        return $this->google2fa->verifyKey($secret, $token);
    }
}
