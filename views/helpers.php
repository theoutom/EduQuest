<?php

declare(strict_types=1);

function h(string|int|null $text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfField(string $csrf): void
{
    echo '<input type="hidden" name="csrf" value="' . h($csrf) . '">';
}

function formatDuration(int $seconds): string
{
    $minutes = intdiv($seconds, 60);
    $remaining = $seconds % 60;
    if ($minutes === 0) {
        return "$remaining detik";
    }
    return "$minutes menit" . ($remaining ? " $remaining detik" : '');
}

function quizLink(array $item): string
{
    return '/kuis/' . $item['slug'];
}

function navCurrent(string $path, string $href): bool
{
    return $path === $href || ($href === '/kuis' && preg_match('~^/(kuis|sesi|hasil)/~', $path) === 1)
        || (in_array($href, ['/admin/kuis', '/admin/siswa', '/admin/komunitas', '/komunitas', '/mentor', '/admin/mentor'], true)
            && str_starts_with($path, $href . '/'));
}

function formValue(array $data, string $key, string|int $fallback = ''): string|int
{
    $value = $data[$key] ?? null;
    return is_string($value) || is_int($value) ? $value : $fallback;
}

function localDate(string $value): string
{
    return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('Asia/Jakarta'))->format('d/m/Y H:i') . ' WIB';
}

function mentorRate(int $amount): string
{
    return $amount === 0 ? 'Gratis' : 'Rp' . number_format($amount, 0, ',', '.') . '/jam';
}
