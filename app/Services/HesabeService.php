<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class HesabeService
{
    protected string $merchantCode;
    protected string $accessCode;
    protected string $secretKey;
    protected string $ivKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->merchantCode = Config::get('hesabe.merchant_code');
        $this->accessCode   = Config::get('hesabe.access_code');
        $this->secretKey    = Config::get('hesabe.secret_key');
        $this->ivKey        = Config::get('hesabe.iv_key');
        $this->baseUrl      = rtrim(Config::get('hesabe.base_url'), '/');

        if (strlen($this->secretKey) !== 32) {
            throw new RuntimeException('HESABE_SECRET_KEY must be 32 characters');
        }
        if (strlen($this->ivKey) !== 16) {
            throw new RuntimeException('HESABE_IV_KEY must be 16 characters');
        }
    }

    private function pad(string $data): string
    {
        $block = 16;
        $padLen = $block - (strlen($data) % $block);
        return $data . str_repeat(chr($padLen), $padLen);
    }

    private function unpad(string $data): string
    {
        $pad = ord(substr($data, -1));
        return ($pad >= 1 && $pad <= 16)
            ? substr($data, 0, -$pad)
            : $data;
    }

    private function encrypt(string $json): string
    {
        $padded = $this->pad($json);
        $raw = openssl_encrypt(
            $padded,
            'AES-256-CBC',
            $this->secretKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->ivKey
        );
        if ($raw === false) {
            throw new RuntimeException('Hesabe encryption failed');
        }
        return strtoupper(bin2hex($raw));
    }

    private function decrypt(string $hex): string
    {
        $h = trim(urldecode($hex));
        if (!ctype_xdigit($h) || strlen($h) % 2 !== 0) {
            throw new RuntimeException('Invalid encrypted data format');
        }
        $raw = hex2bin($h);
        if ($raw === false) {
            throw new RuntimeException('Invalid hex data');
        }
        $dec = openssl_decrypt(
            $raw,
            'AES-256-CBC',
            $this->secretKey,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $this->ivKey
        );
        if ($dec === false) {
            throw new RuntimeException('Hesabe decryption failed');
        }
        return $this->unpad($dec);
    }

    public function decryptCallback(string $cipherHex): array
    {
        $json = $this->decrypt($cipherHex);
        $json = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $json);
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Invalid callback JSON: ' . json_last_error_msg()
            );
        }
        return $decoded;
    }

    public function initiatePayment(array $payload): string
    {
       $body = [
    'merchantCode'         => (string)$this->merchantCode,
    'access_code'          => $this->accessCode,
    'amount'               => number_format($payload['amount'], 3, '.', ''),
    'currency'             => $payload['currency']    ?? 'KWD',
    'responseUrl'          => $payload['responseUrl'] ?? route('hesabe.callback'),
    'failureUrl'           => $payload['failureUrl']  ?? route('hesabe.failure'),
    'paymentType'          => (string)($payload['paymentType'] ?? '0'),
    'version'              => '2.0',
    'merchantRefNo'        => $payload['reference_number'] ?? null,
    'orderReferenceNumber' => $payload['reference_number'] ?? null,
    'variable1'            => $payload['reference_number'] ?? null,
    'variable2'            => $payload['variable2'] ?? null,
    'variable3'            => $payload['variable3'] ?? null,
    'variable4'            => $payload['variable4'] ?? null,
    'variable5'            => $payload['variable5'] ?? null,
];
    Log::debug("Step 4 - initiatePayment called with Reference Number: {$payload['reference_number']}");


        $json = json_encode($body, JSON_UNESCAPED_SLASHES);
        $enc  = $this->encrypt($json);

        $response = Http::asForm()
            ->withHeaders([
                'Accept'     => 'application/json',
                'accessCode' => $this->accessCode,
            ])
            ->post("{$this->baseUrl}/checkout", ['data' => $enc]);

        if (!$response->successful()) {
            Log::error('Hesabe checkout HTTP error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new RuntimeException('Hesabe checkout request failed');
        }

        $decrypted = $this->decrypt(trim($response->body()));
        $dec       = json_decode($decrypted, true);

        if (empty($dec['response']['data'])) {
            Log::error('Hesabe checkout malformed', ['decrypted' => $dec]);
            throw new RuntimeException('Invalid checkout response: ' . ($dec['message'] ?? ''));
        }

        $token = $dec['response']['data'];

        return "{$this->baseUrl}/payment?data={$token}";
    }
}