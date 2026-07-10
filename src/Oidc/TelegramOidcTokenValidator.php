<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\Oidc;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class TelegramOidcTokenValidator
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @return array{id: int, name: string, preferred_username?: string, picture?: string, phone_number?: string}
     */
    public function validate(string $idToken, int $clientId): array
    {
        $jwks = $this->fetchJwks();
        $signer = new Sha256();

        foreach ($jwks['keys'] as $jwk) {
            $key = $this->jwkToKey($jwk);
            $config = Configuration::forSymmetricSigner($signer, $key);

            try {
                $token = $config->parser()->parse($idToken);
            } catch (\Throwable) {
                continue;
            }

            if (null === $token) {
                continue;
            }

            $constraints = [
                new IssuedBy('https://oauth.telegram.org'),
                new SignedWith($signer, $key),
            ];

            if ($config->validation()->validate($token, ...$constraints)) {
                $claims = $token->claims()->all();

                return [
                    'id' => (int) ($claims['id'] ?? $claims['sub'] ?? 0),
                    'name' => $claims['name'] ?? '',
                    'preferred_username' => $claims['preferred_username'] ?? null,
                    'picture' => $claims['picture'] ?? null,
                    'phone_number' => $claims['phone_number'] ?? null,
                ];
            }
        }

        throw new \RuntimeException('Invalid Telegram ID token.');
    }

    /**
     * @return array{keys: list<array>}
     */
    private function fetchJwks(): array
    {
        $response = $this->httpClient->request('GET', 'https://oauth.telegram.org/.well-known/jwks.json');

        return $response->toArray();
    }

    private function jwkToKey(array $jwk): InMemory
    {
        $modulus = $this->base64UrlDecode($jwk['n']);
        $exponent = $this->base64UrlDecode($jwk['e']);

        $pem = $this->buildRsaPublicKeyPem($modulus, $exponent);

        return InMemory::plainText($pem);
    }

    private function buildRsaPublicKeyPem(string $modulus, string $exponent): string
    {
        $modulusHex = bin2hex($modulus);
        $exponentHex = bin2hex($exponent);

        $key = "-----BEGIN PUBLIC KEY-----\n";
        $key .= chunk_split(base64_encode($this->buildDerSequence(
            $this->buildDerSequence(
                $this->buildOid('1.2.840.113549.1.1.1') . $this->buildDerNull()
            ) . $this->buildDerBitString($this->buildDerSequence(
                $this->buildDerInteger($this->hexToBytes($modulusHex)) .
                $this->buildDerInteger($this->hexToBytes($exponentHex))
            ))
        )), 64, "\n");
        $key .= "-----END PUBLIC KEY-----\n";

        return $key;
    }

    private function buildOid(string $oid): string
    {
        $parts = explode('.', $oid);
        $bytes = chr((int) $parts[0] * 40 + (int) $parts[1]);

        for ($i = 2; $i < count($parts); ++$i) {
            $value = (int) $parts[$i];
            if ($value < 128) {
                $bytes .= chr($value);
            } else {
                $temp = [];
                while ($value > 0) {
                    $temp[] = $value & 0x7F;
                    $value >>= 7;
                }
                $temp = array_reverse($temp);
                foreach ($temp as $j => $v) {
                    if ($j > 0) {
                        $v |= 0x80;
                    }
                    $bytes .= chr($v);
                }
            }
        }

        return "\x06" . $this->buildDerLength(strlen($bytes)) . $bytes;
    }

    private function buildDerNull(): string
    {
        return "\x05\x00";
    }

    private function buildDerSequence(string $content): string
    {
        return "\x30" . $this->buildDerLength(strlen($content)) . $content;
    }

    private function buildDerBitString(string $content): string
    {
        return "\x03" . $this->buildDerLength(strlen($content) + 1) . "\x00" . $content;
    }

    private function buildDerInteger(string $bytes): string
    {
        if (ord($bytes[0]) & 0x80) {
            $bytes = "\x00" . $bytes;
        }

        return "\x02" . $this->buildDerLength(strlen($bytes)) . $bytes;
    }

    private function buildDerLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF) . $bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)) . $bytes;
    }

    private function hexToBytes(string $hex): string
    {
        $bytes = '';
        for ($i = 0; $i < strlen($hex); $i += 2) {
            $bytes .= chr(hexdec(substr($hex, $i, 2)));
        }

        return $bytes;
    }

    private function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'), true);
    }
}
