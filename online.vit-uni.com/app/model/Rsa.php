<?php
namespace app\model;

/**
 * RSA 加密工具类
 * 前端使用公钥加密账号密码，后端使用私钥解密
 */
class Rsa
{
    const PRIVATE_KEY_PATH = 'config/certs/rsa_private_key.pem';
    const PUBLIC_KEY_PATH  = 'config/certs/rsa_public_key.pem';

    /**
     * 获取公钥（供前端加密使用）
     * 去掉 PEM 头尾及换行，仅返回纯 base64 内容
     */
    public static function publicKey(): string
    {
        $key = file_get_contents(base_path() . '/' . self::PUBLIC_KEY_PATH);
        if ($key === false) {
            return '';
        }
        $key = preg_replace('/-----BEGIN PUBLIC KEY-----|-----END PUBLIC KEY-----/', '', (string)$key);
        return (string)preg_replace('/\s+/', '', $key);
    }

    /**
     * 使用私钥解密
     * @param string $cipherBase64 前端 base64 编码的密文
     * @return string|null 解密后的明文，失败返回 null
     */
    public static function decrypt($cipherBase64): ?string
    {
        $cipher = strtr((string)$cipherBase64, '-_', '+/');
        $cipher = base64_decode($cipher);
        if ($cipher === false || $cipher === '') {
            return null;
        }

        $privateKey = file_get_contents(base_path() . '/' . self::PRIVATE_KEY_PATH);
        if ($privateKey === false) {
            return null;
        }

        // 优先 PKCS1 v1.5（JSEncrypt 默认），失败再尝试 OAEP
        foreach ([OPENSSL_PKCS1_PADDING, OPENSSL_PKCS1_OAEP_PADDING] as $padding) {
            $plain = '';
            if (openssl_private_decrypt($cipher, $plain, $privateKey, $padding)) {
                return $plain;
            }
        }
        return null;
    }

    /**
     * 使用公钥加密（测试用）
     */
    public static function encrypt(string $plain, int $padding = OPENSSL_PKCS1_PADDING): ?string
    {
        $publicKey = file_get_contents(base_path() . '/' . self::PUBLIC_KEY_PATH);
        $cipher = '';
        if (openssl_public_encrypt($plain, $cipher, $publicKey, $padding)) {
            return base64_encode($cipher);
        }
        return null;
    }
}