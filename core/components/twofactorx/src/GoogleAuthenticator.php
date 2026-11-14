<?php

namespace TreehillStudio\TwoFactorX;

use PHPGangsta_GoogleAuthenticator;

class GoogleAuthenticator extends PHPGangsta_GoogleAuthenticator
{
    /**
     * Get the time slice the code is valid for, the latest time slice is checked first.
     *
     * @param string $secret
     * @param string $code
     * @param int $discrepancy The allowed time drift in 30 second units
     * @return int|null The matching time slice or null if the code does not match
     */
    public function getMatchingTimeSlice($secret, $code, int $discrepancy = 1): ?int
    {
        $currentTimeSlice = (int)floor(time() / 30);
        for ($i = $discrepancy; $i >= -$discrepancy; --$i) {
            if ($this->verifyCode($secret, (string)$code, 0, $currentTimeSlice + $i)) {
                return $currentTimeSlice + $i;
            }
        }
        return null;
    }

    /**
     * @param $secret
     * @return bool
     */
    public function isSecretValid($secret)
    {
        if (strlen($secret) == 16) {
            $chars = str_split($secret);
            foreach ($chars as $char) {
                if (!in_array($char, $this->_getBase32LookupTable())) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
