<?php

declare(strict_types=1);

final class Tutor
{
    public const INTENTS = ['mulai' => 'Bantu mulai', 'langkah' => 'Urai langkah', 'contoh' => 'Beri latihan pembanding'];

    private const GUIDES = [
        'modulo' => [
            'mulai' => 'Tandai bilangan yang dibagi dan pembaginya. Cari sisa pembagian, lalu cocokkan dengan kondisi pada soal.',
            'langkah' => 'Cari kelipatan pembagi yang masih berada di bawah atau sama dengan bilangan awal. Kurangi bilangan awal dengan kelipatan itu dan periksa sisanya.',
            'contoh' => 'Ambil bilangan dan pembagi kecil yang berbeda dari soal. Gambar keduanya sebagai kelompok dengan ukuran sama, lalu hitung benda yang tersisa.',
        ],
        'sql' => [
            'mulai' => 'Tentukan tabel sumber serta baris yang harus diperiksa. Jejakkan satu baris sebelum mencoba membaca seluruh hasil.',
            'langkah' => 'Pisahkan pencocokan, penyaringan, dan pengelompokan. Catat kapan tiap kondisi diterapkan pada baris atau kelompok.',
            'contoh' => 'Buat dua tabel kecil di kertas. Sertakan satu baris yang punya pasangan dan satu yang tidak, lalu prediksi bentuk hasil.',
        ],
        'struktur' => [
            'mulai' => 'Tulis operasi yang diminta dan data apa yang tersedia saat operasi dimulai. Jejakkan satu contoh kecil.',
            'langkah' => 'Catat urutan data masuk, posisi yang diakses, dan bagian yang berubah setelah setiap operasi. Bandingkan jejaknya dengan tuntutan soal.',
            'contoh' => 'Coba tiga elemen berlabel berbeda. Jalankan satu operasi pada tiap langkah dan catat elemen yang keluar atau berubah.',
        ],
        'web' => [
            'mulai' => 'Pisahkan tindakan yang diminta klien dari hasil yang dikirim server. Catat apakah tindakan mengubah sumber daya.',
            'langkah' => 'Tuliskan permintaan dan respons dalam dua kolom. Untuk masing-masing, catat tujuan, keadaan sebelum tindakan, dan keadaan setelahnya.',
            'contoh' => 'Bayangkan satu permintaan membaca daftar dan satu membuat entri. Bandingkan hasil yang diharapkan dari keduanya.',
        ],
        'algoritma' => [
            'mulai' => 'Tentukan masukan dan keluaran yang dicari. Tandai aturan atau kondisi yang bisa mengubah langkah.',
            'langkah' => 'Buat tabel berisi langkah, nilai awal, dan nilai setelah operasi. Telusuri satu cabang pada setiap langkah.',
            'contoh' => 'Gunakan masukan kecil yang berbeda dari soal. Coba satu kondisi yang terpenuhi dan satu yang tidak terpenuhi.',
        ],
        'umum' => [
            'mulai' => 'Pisahkan hal yang sudah diketahui dari hal yang ditanyakan. Tandai istilah yang perlu kamu jelaskan dengan bahasamu sendiri.',
            'langkah' => 'Tentukan satu aturan yang relevan. Uji satu kemungkinan pada satu waktu untuk menemukan alasan mengapa aturan itu cocok atau tidak.',
            'contoh' => 'Buat contoh yang lebih sederhana dengan aturan yang sama. Setelah memeriksanya, coba ubah satu kondisi dan lihat apa yang berubah.',
        ],
    ];

    private const FOLLOW_UP = [
        'Apa langkah pertama yang bisa kamu tulis sendiri?',
        'Kondisi mana yang perlu diperiksa sebelum melanjutkan?',
        'Bagaimana kamu memeriksa hasilnya dengan contoh kecil?',
    ];

    public function __construct(private PDO $db)
    {
    }

    public function hint(int $student, string $topic, string $stem, string $intent): array
    {
        if (!isset(self::INTENTS[$intent])) {
            throw new QuizProblem(422, 'Pilih jenis bantuan yang tersedia.');
        }
        $statement = $this->db->prepare(<<<'SQL'
            INSERT INTO eduquest.tutor_limits AS t VALUES (?, statement_timestamp() + interval '1 minute', 1)
            ON CONFLICT (student_id) DO UPDATE SET
                requests = CASE WHEN t.window_end <= statement_timestamp() THEN 1 ELSE LEAST(t.requests + 1, 9) END,
                window_end = CASE WHEN t.window_end <= statement_timestamp()
                    THEN statement_timestamp() + interval '1 minute' ELSE t.window_end END
            RETURNING requests, GREATEST(1, ceil(extract(epoch FROM window_end - statement_timestamp())))::int AS retry
            SQL);
        $statement->execute([$student]);
        $limit = $statement->fetch();
        if ($limit['requests'] > 8) {
            header('Retry-After: ' . $limit['retry']);
            throw new QuizProblem(429, 'Batas bantuan sementara tercapai. Coba lagi dalam satu menit.');
        }
        $family = self::family($topic, $stem);
        $provider = getenv('EDUQUEST_TUTOR_PROVIDER') ?: '9router';
        $choice = match ($provider) {
            '9router', 'vercel' => TutorGateway::fromEnvironment($provider)->choose($family, $intent)['choice'],
            'ollama' => self::ollamaChoice($family, $intent),
            default => null,
        };
        return [
            'hint' => self::GUIDES[$family][$intent],
            'prompt' => self::FOLLOW_UP[$choice ?? array_search($intent, array_keys(self::INTENTS), true)],
            'source' => $choice === null ? 'panduan' : $provider,
        ];
    }

    private static function family(string $topic, string $stem): string
    {
        if (preg_match('/\b(modulus|modulo|sisa pembagian)\b/ui', $stem)) {
            return 'modulo';
        }
        if ($topic === 'basis-data' || preg_match('/\b(sql|left join|having|foreign key)\b/ui', $stem)) {
            return 'sql';
        }
        if ($topic === 'web' || preg_match('/\b(http|rest api|kode status)\b/ui', $stem)) {
            return 'web';
        }
        if (preg_match('/\b(stack|queue|array|hash|struktur data|undo)\b/ui', $stem)) {
            return 'struktur';
        }
        return $topic === 'pemrograman' ? 'algoritma' : 'umum';
    }

    private static function ollamaChoice(string $family, string $intent): ?int
    {
        $model = getenv('EDUQUEST_TUTOR_MODEL') ?: '';
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:\/-]{0,100}$/D', $model)
            || str_contains(strtolower($model), 'cloud') || !extension_loaded('curl')) {
            return null;
        }
        $port = filter_var(getenv('EDUQUEST_TUTOR_OLLAMA_PORT') ?: '11434', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
        if ($port === false) {
            return null;
        }
        $request = json_encode([
            'model' => $model,
            'prompt' => "Select a Socratic follow-up for concept $family and help request $intent. Return JSON with one integer choice: 0 for a first action, 1 for a condition check, or 2 for a small example.",
            'stream' => false, 'format' => 'json', 'think' => false,
            'options' => ['num_predict' => 24, 'temperature' => 0],
        ], JSON_THROW_ON_ERROR);
        $response = '';
        $curl = curl_init("http://127.0.0.1:$port/api/generate");
        curl_setopt_array($curl, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT_MS => 400, CURLOPT_TIMEOUT_MS => 2200,
            CURLOPT_FOLLOWLOCATION => false, CURLOPT_NOPROXY => '*',
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$response): int {
                if (strlen($response) + strlen($chunk) > 8192) {
                    return 0;
                }
                $response .= $chunk;
                return strlen($chunk);
            },
        ]);
        if (curl_exec($curl) === false || curl_getinfo($curl, CURLINFO_HTTP_CODE) !== 200) {
            return null;
        }
        $envelope = json_decode($response, true);
        $payload = is_string($envelope['response'] ?? null) ? json_decode($envelope['response'], true) : null;
        $choice = $payload['choice'] ?? null;
        return is_int($choice) && isset(self::FOLLOW_UP[$choice]) ? $choice : null;
    }
}
