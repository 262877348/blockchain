<?php

namespace think\Support;

use InvalidArgumentException;

/**
 * Class Address
 *
 * @package think
 */
class Address
{
    public string $address_hex = '';
    public string $address;
    public string $private_key;

    const ADDRESS_SIZE        = 34;
    const ADDRESS_PREFIX      = "41";
    const ADDRESS_PREFIX_BYTE = 0x41;

    public function __construct(string $address = '', string $private_key = '', string $address_hex = '')
    {
        if (strlen($address) === 0) {
            throw new InvalidArgumentException('Address can not be empty');
        }

        $this->private_key = $private_key;
        $this->address     = $address;
        $this->address_hex = $address_hex;
    }

    /**
     * Dont rely on this. Always use Wallet::validateAddress to double check
     * against tronGrid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (strlen($this->address) !== Address::ADDRESS_SIZE) {
            return false;
        }

        $address = Base58Check::decode($this->address, false, 0, false);
        $utf8    = hex2bin($address);

        if (strlen($utf8) !== 25) {
            return false;
        }

        if (strpos($utf8, self::ADDRESS_PREFIX_BYTE) !== 0) {
            return false;
        }

        $checkSum = substr($utf8, 21);
        $address  = substr($utf8, 0, 21);

        $hash0     = Hash::SHA256($address);
        $hash1     = Hash::SHA256($hash0);
        $checkSum1 = substr($hash1, 0, 4);

        if ($checkSum === $checkSum1) {
            return true;
        }

        return false;
    }
}