<?php

declare(strict_types=1);

function postgresVerifier(string $asciiPassword): string
{
    $iterations = 16384;
    $salt = random_bytes(16);
    $salted = hash_pbkdf2('sha256', $asciiPassword, $salt, $iterations, 32, true);
    $client = hash_hmac('sha256', 'Client Key', $salted, true);
    $stored = hash('sha256', $client, true);
    $server = hash_hmac('sha256', 'Server Key', $salted, true);
    return 'SCRAM-SHA-256$' . $iterations . ':' . base64_encode($salt)
        . '$' . base64_encode($stored) . ':' . base64_encode($server);
}
