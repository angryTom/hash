<?php
/**
 * Blowfish.php
 *
 * @category        AngryBytes
 * @package         Hash
 * @subpackage      Hasher
 * @copyright       Copyright (c) 2007-2016 Angry Bytes BV (http://www.angrybytes.com)
 */

namespace AngryBytes\Hash\Hasher;

use AngryBytes\Hash\Hash;
use AngryBytes\Hash\HasherInterface;

use \RuntimeException;
use \InvalidArgumentException;

/**
 * Blowfish Hasher
 *
 * Generate and verify Blowfish bcrypt/crypt() hashes.
 *
 * @see AngryBytes\Hasher\Password For a password hasher
 * @category        AngryBytes
 * @package         Hash
 * @subpackage      Hasher
 */
class Blowfish implements HasherInterface
{
    /**
     * Work factor for blowfish
     *
     * Defaults to '15' (32768 iterations)
     *
     * @var int
     **/
    private $workFactor = 15;

    /**
     * Construct
     *
     * Detect Blowfish support
     *
     * @throws \RuntimeException
     * @param null|int $workFactor Override workfactor
     */
    public function __construct($workFactor = null)
    {
        if (!defined("CRYPT_BLOWFISH") || CRYPT_BLOWFISH !== 1) {
            throw new RuntimeException(
                'Blowfish hashing not available on this installation'
            );
        }

        if (is_int($workFactor)) {
            $this->setWorkFactor($workFactor);
        }
    }

    /**
     * Get the blowfish work factor
     *
     * @return int
     */
    public function getWorkFactor()
    {
        return $this->workFactor;
    }

    /**
     * Set the blowfish work factor
     *
     * @param  int $workFactor
     * @return Blowfish
     */
    public function setWorkFactor($workFactor)
    {
        if ($workFactor < 4 || $workFactor > 31) {
            throw new InvalidArgumentException(
                'Work factor needs to be greater than 3 and smaller than 32'
            );
        }
        $this->workFactor = (int) $workFactor;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function hash($string, array $options = [])
    {
        $salt = isset($options['salt']) ? $this->bcryptSalt($options['salt']) : null;

        return crypt($string, $salt);
    }

    /**
     * {@inheritDoc}
     *
     * @see Hash::compare()
     */
    public function verify($string, $hash, array $options = [])
    {
        return Hash::compare(
            $this->hash($string, $options),
            $hash
        );
    }

    /**
     * Generate a bcrypt salt from a string salt
     *
     * @param  string $salt
     * @return string       Format: "$2y$[workfactor]$[salt]$"
     **/
    private function bcryptSalt($salt)
    {
        return '$2y$'
            // Pad workfactor with 0's to the left, max 2 chars long
            . str_pad($this->getWorkFactor(), 2, '0', STR_PAD_LEFT)
            // Add salt itself
            . '$' .
            self::getSaltSubstr($salt)
            . '$'
        ;
    }

    /**
     * Get valid salt string for Blowfish usage
     *
     * Blowfish accepts 22 chars (./0-9A-Za-z) as a salt if anything else is passed,
     * this method will take a hash of $salt to transform it into 22 supported characters
     *
     * @param  string $salt
     * @return string
     **/
    private static function getSaltSubstr($salt)
    {
        // Return salt when it is a valid Blowfish salt
        if (preg_match('!^[\./0-9A-Za-z]{22}$!', $salt) === 1) {
            return $salt;
        }

        // fallback to md5() to make the salt valid
        return substr(
            md5($salt),
            0, 22
        );
    }
}
