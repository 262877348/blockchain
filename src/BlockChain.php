<?php
declare(strict_types=1);

namespace think;

use IEXBase\TronAPI\Exception\TronException;
use IEXBase\TronAPI\Provider\HttpProvider;
use IEXBase\TronAPI\Tron;
use kornrunner\Keccak;
use Phactor\Key;
use think\Support\Address;
use think\Support\Base58;
use think\Support\Crypto;
use think\Support\Hash;
use Exception;

class BlockChain
{
    /**
     * 节点
     *
     * @var HttpProvider
     */
    protected $full_node;

    /**
     * 合约节点
     *
     * @var HttpProvider
     */
    protected $solidity_node;

    /**
     * 事件节点
     *
     * @var HttpProvider
     */
    protected $event_Server;

    /**
     * 波场
     *
     * @var Tron
     */
    protected $tron;

    /**
     * 节点
     * https://api.trongrid.io 正式节点
     * https://api.shasta.trongrid.io 测试节点
     *
     * @var string
     */
    protected static $host = "https://api.trongrid.io";

    public function __construct()
    {
        try {
            // 节点
            $this->full_node = new HttpProvider(self::$host);
            // 合约节点
            $this->solidity_node = new HttpProvider(self::$host);
            // 事件节点
            $this->event_Server = new HttpProvider(self::$host);

            // 公链对象
            $this->tron = new Tron($this->full_node, $this->solidity_node, $this->event_Server);
        } catch (TronException $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 本地生成地址
     *
     * @return array|false
     * @throws TronException
     * @throws Exception
     */
    public function generateAddress()
    {
        $attempts      = 0;
        $valid_address = false;
        $address       = [];

        do {
            if ($attempts++ === 5) {
                //这里应该是返回系统错误
                return false;
            }

            $key_pair = $this->genKeyPair();

            //带有0x的私钥。
            $private_key_hex = $key_pair['private_key_hex'];
            $public_key_hex  = $key_pair['public_key'];

            //We cant use hex2bin unless the string length is even.
            if (strlen($public_key_hex) % 2 !== 0) {
                continue;
            }

            $pub_key_bin = hex2bin($public_key_hex);

            $address_hex = $this->getAddressHex($pub_key_bin);

            $address_hex_bin = hex2bin($address_hex);

            $address_base58 = $this->getBase58CheckAddress($address_hex_bin);

            //不带0x的私钥
            $private_key = substr($private_key_hex, 2);

            $address = new Address($address_base58, $private_key, $address_hex);

            $valid = $this->validateAddress($address_base58);

            $valid_address = $valid['result'] ? false : true;
        } while ($valid_address);

        return (array)$address;
    }

    /**
     * 生成KEY
     *
     * @return array
     */
    protected function genKeyPair(): array
    {
        $key = new Key();
        return $key->GenerateKeypair();
    }

    /**
     * 地址转HEX
     *
     * @param string $pub_key_bin
     *
     * @return string
     * @throws Exception
     */
    public function getAddressHex(string $pub_key_bin): string
    {
        if (strlen($pub_key_bin) == 65) {
            $pub_key_bin = substr($pub_key_bin, 1);
        }

        $hash = Keccak::hash($pub_key_bin, 256);

        return Address::ADDRESS_PREFIX . substr($hash, 24);
    }

    /**
     * 获取BASE58
     *
     * @param string $address_bin
     *
     * @return string
     */
    protected function getBase58CheckAddress(string $address_bin): string
    {
        $hash0    = Hash::SHA256($address_bin);
        $hash1    = Hash::SHA256($hash0);
        $checksum = substr($hash1, 0, 4);
        $checksum = $address_bin . $checksum;

        return Base58::encode(Crypto::bin2bc($checksum));
    }

    /**
     * 验证地址
     *
     * @param $address
     *
     * @return array
     * @throws TronException
     */
    public function validateAddress($address)
    {
        return $this->tron->validateAddress($address);
    }
}