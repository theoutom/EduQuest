<?php

declare(strict_types=1);

final class TutorGateway
{
    public const DEFAULT_MODEL = 'google/gemini-2.5-flash-lite';
    private const ENDPOINT = 'https://ai-gateway.vercel.sh/v1/chat/completions';

    public function __construct(
        private string $model,
        private string $token,
        private ?Closure $transport = null,
        private string $provider = 'vercel',
        private string $port = '20128',
    ) {
    }

    public static function fromEnvironment(string $provider = 'vercel', ?Closure $transport = null): self
    {
        if ($provider === '9router') {
            return new self(
                getenv('EDUQUEST_TUTOR_9ROUTER_MODEL') ?: '',
                getenv('EDUQUEST_9ROUTER_API_KEY') ?: '',
                $transport,
                '9router',
                getenv('EDUQUEST_TUTOR_9ROUTER_PORT') ?: '20128',
            );
        }
        return new self(
            getenv('EDUQUEST_TUTOR_GATEWAY_MODEL') ?: self::DEFAULT_MODEL,
            getenv('AI_GATEWAY_API_KEY') ?: (getenv('VERCEL_OIDC_TOKEN') ?: ''),
            $transport,
        );
    }

    public function readiness(): string
    {
        if ($this->token === '') {
            return 'missing_credential';
        }
        if (!preg_match('/^[!-~]+$/D', $this->token) || strlen($this->token) > 12288) {
            return 'invalid_credential';
        }
        $pattern = $this->provider === '9router'
            ? '~^[a-zA-Z0-9][a-zA-Z0-9._:-]{0,120}(?:/[a-zA-Z0-9][a-zA-Z0-9._:-]{0,120})?$~D'
            : '~^[a-z0-9][a-z0-9_-]*/[a-zA-Z0-9][a-zA-Z0-9._:-]{0,120}$~D';
        if (!preg_match($pattern, $this->model)) {
            return 'invalid_model';
        }
        if ($this->provider === '9router' && filter_var($this->port, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]) === false) {
            return 'invalid_port';
        }
        return $this->transport !== null || extension_loaded('curl') ? 'ready' : 'missing_curl';
    }

    public function choose(string $family, string $intent): array
    {
        $status = $this->readiness();
        if ($status !== 'ready') {
            return ['status' => $status, 'choice' => null];
        }
        if (!in_array($family, ['modulo', 'sql', 'web', 'struktur', 'algoritma', 'umum'], true)
            || !in_array($intent, ['mulai', 'langkah', 'contoh'], true)) {
            return ['status' => 'invalid_context', 'choice' => null];
        }

        $request = [
            'model' => $this->model,
            'messages' => [[
                'role' => 'user',
                'content' => "Choose a Socratic follow-up for concept $family and help request $intent. "
                    . 'Choose 0 for first action, 1 for condition check, or 2 for a small example. Return only a JSON object with the integer choice.',
            ]],
            'stream' => false,
            'max_tokens' => 96,
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'tutor_choice',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => ['choice' => ['type' => 'integer', 'enum' => [0, 1, 2]]],
                        'required' => ['choice'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
        ];
        $endpoint = self::ENDPOINT;
        if ($this->provider === '9router') {
            $endpoint = 'http://127.0.0.1:' . (int) $this->port . '/v1/chat/completions';
            unset($request['response_format']);
        }
        $transport = $this->transport ?? self::request(...);
        [$code, $response] = $transport($endpoint, $this->token, $request);
        if ($code !== 200) {
            return ['status' => $code === 0 ? 'transport_error' : "http_$code", 'choice' => null];
        }
        $result = json_decode($response, true);
        $completion = $result['choices'][0] ?? null;
        $content = $completion['message']['content'] ?? null;
        $decoded = ($completion['finish_reason'] ?? null) === 'stop' && is_string($content)
            ? json_decode($content, true) : null;
        $choice = $decoded['choice'] ?? null;
        if (!is_int($choice) || $choice < 0 || $choice > 2 || count($decoded) !== 1) {
            return ['status' => 'invalid_response', 'choice' => null];
        }
        return ['status' => 'ok', 'choice' => $choice];
    }

    private static function request(string $url, string $token, array $body): array
    {
        $response = '';
        $curl = curl_init($url);
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_CONNECTTIMEOUT_MS => 850,
            CURLOPT_TIMEOUT_MS => 3400,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$response): int {
                if (strlen($response) + strlen($chunk) > 16384) {
                    return 0;
                }
                $response .= $chunk;
                return strlen($chunk);
            },
        ];
        if (str_starts_with($url, 'http://127.0.0.1:')) {
            $options[CURLOPT_NOPROXY] = '*';
        }
        curl_setopt_array($curl, $options);
        if (curl_exec($curl) === false) {
            return [0, ''];
        }
        return [(int) curl_getinfo($curl, CURLINFO_HTTP_CODE), $response];
    }
}
