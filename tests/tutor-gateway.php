<?php

declare(strict_types=1);

$file = dirname(__DIR__) . '/app/TutorGateway.php';
if (!is_file($file)) {
    fwrite(STDERR, "FAIL: Tutor Gateway adapter is missing.\n");
    exit(1);
}
require $file;

function assertGateway(bool $condition): void
{
    if (!$condition) {
        throw new RuntimeException('Gateway contract failed.');
    }
}

$calls = [];
$code = 200;
$message = '{"choice":2}';
$transport = static function (string $url, string $token, array $body) use (&$calls, &$code, &$message): array {
    $calls[] = [$url, $token, $body];
    return [$code, json_encode(['choices' => [['finish_reason' => 'stop', 'message' => ['content' => $message]]]])];
};
$gateway = new TutorGateway('google/gemini-2.5-flash-lite', 'fixture-credential', $transport);
assertGateway($gateway->choose('sql', 'mulai') === ['status' => 'ok', 'choice' => 2]);
[$url, $token, $body] = $calls[0];
assertGateway($url === 'https://ai-gateway.vercel.sh/v1/chat/completions' && $token === 'fixture-credential');
assertGateway($body['model'] === 'google/gemini-2.5-flash-lite' && $body['stream'] === false);
assertGateway($body['max_tokens'] === 96);
assertGateway($body['response_format']['json_schema']['schema']['properties']['choice']['enum'] === [0, 1, 2]);
assertGateway(count($body['messages']) === 1 && str_contains($body['messages'][0]['content'], 'sql'));
$message = '{"choice":"RAHASIA"}';
assertGateway($gateway->choose('sql', 'mulai') === ['status' => 'invalid_response', 'choice' => null]);
$message = '{"choice":1,"hint":"extra content"}';
assertGateway($gateway->choose('sql', 'mulai')['status'] === 'invalid_response');
$code = 429;
assertGateway($gateway->choose('sql', 'mulai') === ['status' => 'http_429', 'choice' => null]);
$before = count($calls);
assertGateway($gateway->choose('private student content', 'mulai')['choice'] === null);
assertGateway((new TutorGateway('google/gemini-2.5-flash-lite', '', $transport))->choose('sql', 'mulai')['status'] === 'missing_credential');
assertGateway(count($calls) === $before);
putenv('AI_GATEWAY_API_KEY=must-not-reach-9router');
putenv('EDUQUEST_9ROUTER_API_KEY=router-fixture');
putenv('EDUQUEST_TUTOR_9ROUTER_MODEL=latihan');
putenv('EDUQUEST_TUTOR_9ROUTER_PORT=20129');
$code = 200;
$message = '{"choice":1}';
$router = TutorGateway::fromEnvironment('9router', $transport);
assertGateway($router->choose('modulo', 'langkah') === ['status' => 'ok', 'choice' => 1]);
[$url, $token, $body] = $calls[array_key_last($calls)];
assertGateway($url === 'http://127.0.0.1:20129/v1/chat/completions' && $token === 'router-fixture');
assertGateway($body['model'] === 'latihan' && $body['max_tokens'] === 96 && !isset($body['response_format']));
putenv('EDUQUEST_TUTOR_9ROUTER_PORT=65536');
assertGateway(TutorGateway::fromEnvironment('9router', $transport)->readiness() === 'invalid_port');
putenv('EDUQUEST_9ROUTER_API_KEY');
assertGateway(TutorGateway::fromEnvironment('9router', $transport)->readiness() === 'missing_credential');
echo "PASS: Gateway endpoint, closed payload, strict choice, missing credential and quota response\n";
