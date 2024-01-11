<?php

namespace TreehillStudio\TwoFactorX;

use PHPGangsta_GoogleAuthenticator;

class GoogleAuthenticator extends PHPGangsta_GoogleAuthenticator
{
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
