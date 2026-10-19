<?php

namespace App\Services;

use App\Models\Material;
use App\Models\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class QuestionGenerator
{
    // Komposisi kesulitan tiap 5 soal
    public const MIX = ['mudah', 'mudah', 'sedang', 'sedang', 'sulit'];

    private const LEVELS = [
        'mudah' => 'mengingat definisi, istilah, atau fakta dasar',
        'sedang' => 'memahami dan menerapkan konsep pada contoh sederhana',
        'sulit' => 'menganalisis studi kasus atau membandingkan beberapa konsep',
    ];

    public function enabled(): bool
    {
        return filled(config('services.groq.key'));
    }

    /**
     * Buat soal aktif baru untuk satu materi.
     *
     * @param  list<string>  $difficulties
     * @return Collection<int, Question>
     */
    public function generate(Material $material, array $difficulties): Collection
    {
        $material->loadMissing('course');

        $existing = Question::query()
            ->whereHas('material', fn ($q) => $q->where('course_id', $material->course_id))
            ->latest()->limit(40)->pluck('question_text');

        $response = Http::withToken(config('services.groq.key'))
            ->timeout(90)
            ->retry(2, 1000, throw: false)
            ->post(config('services.groq.url'), [
                'model' => config('services.groq.model'),
                'temperature' => 0.8,
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => $this->userPrompt($material, $difficulties, $existing)],
                ],
                'response_format' => ['type' => 'json_schema', 'json_schema' => $this->schema()],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Groq gagal: HTTP '.$response->status().' '.Str::limit($response->body(), 300));
        }

        $items = json_decode((string) $response->json('choices.0.message.content'), true)['questions'] ?? [];
        $seen = $existing->map(fn ($text) => $this->normalize($text))->all();
        $wanted = collect($difficulties)->countBy()->all();
        $created = collect();

        foreach ($items as $item) {
            $item = $this->tidy($item);
            $level = $item['difficulty'] ?? null;

            if (! $this->valid($item) || ($wanted[$level] ?? 0) < 1 || in_array($this->normalize($item['question']), $seen, true)) {
                continue;
            }

            $created->push($this->store($material, $item));
            $seen[] = $this->normalize($item['question']);
            $wanted[$level]--;
        }

        return $created;
    }

    private function store(Material $material, array $item): Question
    {
        // Acak posisi kunci jawaban
        $options = collect(Question::ANSWERS)->map(fn ($key) => [
            'text' => trim($item['option_'.$key]),
            'correct' => $key === $item['answer'],
        ])->shuffle()->values();

        return Question::create([
            'material_id' => $material->id,
            'question_text' => trim($item['question']),
            'option_a' => $options[0]['text'],
            'option_b' => $options[1]['text'],
            'option_c' => $options[2]['text'],
            'option_d' => $options[3]['text'],
            'correct_answer' => Question::ANSWERS[$options->search(fn ($o) => $o['correct'])],
            'difficulty' => $item['difficulty'],
            'status' => Question::STATUS_ACTIVE,
            'source' => 'ai',
        ]);
    }

    private function valid(array $item): bool
    {
        if (! isset(self::LEVELS[$item['difficulty'] ?? '']) || ! in_array($item['answer'] ?? null, Question::ANSWERS, true)) {
            return false;
        }

        $options = collect(Question::ANSWERS)->map(fn ($key) => Str::lower(trim((string) ($item['option_'.$key] ?? ''))));

        return filled(trim((string) ($item['question'] ?? '')))
            && $options->filter()->count() === 4
            && $options->unique()->count() === 4;
    }

    // Buang nomor atau label di awal teks
    private function tidy(array $item): array
    {
        $item['question'] = trim((string) preg_replace('/^\s*(?:(?:soal|pertanyaan)\s*(?:nomor\s*)?\d*|\d+)\s*[:.)\-]\s*/iu', '', (string) ($item['question'] ?? '')));

        foreach (Question::ANSWERS as $key) {
            $item['option_'.$key] = trim((string) preg_replace('/^\s*\(?[a-d]\s*[.)]\s+/iu', '', (string) ($item['option_'.$key] ?? '')));
        }

        return $item;
    }

    private function normalize(string $text): string
    {
        return Str::of($text)->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->trim()->value();
    }

    private function systemPrompt(): string
    {
        return 'Kamu dosen Sistem Informasi yang menyusun soal pilihan ganda untuk kuis duel mahasiswa S1. '
            .'Tulis dalam bahasa Indonesia baku dan ringkas. Setiap soal punya tepat satu jawaban benar yang tidak bisa diperdebatkan, '
            .'tiga pengecoh yang masuk akal tapi jelas salah bagi yang paham materi, tanpa opsi "semua benar" atau "tidak ada yang benar". '
            .'Pertanyaan maksimal 300 karakter, tiap opsi maksimal 120 karakter. Pastikan kunci jawaban benar secara faktual. '
            .'Tulis pertanyaan langsung tanpa nomor, label, atau awalan seperti "Soal 1:", dan opsi tanpa huruf A sampai D di depannya.';
    }

    private function userPrompt(Material $material, array $difficulties, Collection $existing): string
    {
        $counts = collect($difficulties)->countBy()
            ->map(fn ($n, $level) => "- {$n} soal {$level}: ".self::LEVELS[$level])
            ->implode("\n");

        $course = $material->course;
        $avoid = $existing->isEmpty() ? '(belum ada)' : $existing->map(fn ($text) => '- '.Str::limit($text, 140))->implode("\n");

        return <<<TXT
        Mata kuliah: {$course->name} ({$course->code}), semester {$course->semester}.
        Materi: {$material->title}.
        Deskripsi materi: {$material->description}

        Buat soal baru dengan komposisi tingkat kesulitan berikut:
        {$counts}

        Topik tiap soal harus berbeda satu sama lain dan berbeda dari soal yang sudah ada di bawah ini:
        {$avoid}
        TXT;
    }

    private function schema(): array
    {
        $option = ['type' => 'string'];

        return [
            'name' => 'soal_pilihan_ganda',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'properties' => [
                    'questions' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'difficulty' => ['type' => 'string', 'enum' => array_keys(self::LEVELS)],
                                'question' => ['type' => 'string'],
                                'option_a' => $option,
                                'option_b' => $option,
                                'option_c' => $option,
                                'option_d' => $option,
                                'answer' => ['type' => 'string', 'enum' => Question::ANSWERS],
                            ],
                            'required' => ['difficulty', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'answer'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
                'required' => ['questions'],
                'additionalProperties' => false,
            ],
        ];
    }
}
