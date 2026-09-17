<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OllamaReportService
{
    /**
     * Generate a professional session report draft from keywords.
     *
     * @param  array{player_name?: string, sport?: string, age?: string}  $context
     * @return array{
     *   title: string,
     *   summary: string,
     *   focus: string,
     *   went_well: string,
     *   needs_work: string,
     *   home: string,
     *   videos: list<array{title: string, meta: string}>,
     *   source: string
     * }
     */
    public function generate(string $keywords, array $context = []): array
    {
        $keywords = trim($keywords);
        if ($keywords === '') {
            throw new RuntimeException('Enter a few session keywords first.');
        }

        try {
            return $this->generateWithOllama($keywords, $context);
        } catch (\Throwable $e) {
            if (! config('coachnow.ollama.fallback_enabled', true)) {
                throw $e;
            }

            report($e);

            $fallback = $this->professionalFallback($keywords, $context);
            $fallback['source'] = 'fallback';
            $fallback['warning'] = $e->getMessage();

            return $fallback;
        }
    }

    /**
     * @param  array{player_name?: string, sport?: string, age?: string}  $context
     * @return array<string, mixed>
     */
    private function generateWithOllama(string $keywords, array $context): array
    {
        $baseUrl = rtrim((string) config('coachnow.ollama.base_url', 'http://127.0.0.1:11434'), '/');
        $model = (string) config('coachnow.ollama.model', 'llama3.2:3b');
        $timeout = (int) config('coachnow.ollama.timeout', 120);

        if (! $this->isReachable($baseUrl)) {
            throw new RuntimeException(
                'AI is not available right now. Check that the local AI service is running, then try again.'
            );
        }

        $player = trim((string) ($context['player_name'] ?? 'the player'));
        $sport = trim((string) ($context['sport'] ?? 'soccer'));
        $age = trim((string) ($context['age'] ?? ''));

        $system = <<<'PROMPT'
You are an elite youth sports coach writing professional post-session development reports for CoachNow.
Write clear, encouraging, specific coaching language. Avoid fluff, emojis, and markdown.
Always return ONLY valid JSON with these exact keys:
{
  "title": "short report title",
  "summary": "1-2 sentence overview for the player/parent",
  "focus": "focus of the week (2-4 sentences, actionable)",
  "went_well": "what went well (2-4 sentences, specific)",
  "needs_work": "needs work (2-4 sentences, constructive)",
  "home": "home training plan with 3 concrete drills, each on its own line starting with a number"
}
PROMPT;

        $user = "Player: {$player}\nSport: {$sport}".($age !== '' && $age !== '—' ? "\nAge/group: {$age}" : '')
            ."\nSession keywords: {$keywords}\n"
            ."Write a professional private-session report tailored to these keywords.";

        $response = Http::timeout($timeout)
            ->acceptJson()
            ->post($baseUrl.'/api/chat', [
                'model' => $model,
                'stream' => false,
                'format' => 'json',
                'options' => [
                    'temperature' => 0.35,
                    'num_predict' => 900,
                ],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if ($response->status() === 404) {
            throw new RuntimeException(
                'AI model is not ready yet. Please try again in a moment.'
            );
        }

        if (! $response->successful()) {
            throw new RuntimeException(
                'AI request failed. Please try again in a moment.'
            );
        }

        $content = (string) data_get($response->json(), 'message.content', '');
        $parsed = $this->parseJsonContent($content);

        if (! $parsed) {
            throw new RuntimeException('AI returned an incomplete draft. Please try again.');
        }

        return $this->normalizeReport($parsed, $keywords, 'ollama');
    }

    public function isReachable(?string $baseUrl = null): bool
    {
        $baseUrl = rtrim($baseUrl ?: (string) config('coachnow.ollama.base_url', 'http://127.0.0.1:11434'), '/');

        try {
            $response = Http::timeout(3)->get($baseUrl.'/api/tags');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseJsonContent(string $content): ?array
    {
        $content = trim($content);
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $content, $m)) {
            $decoded = json_decode($m[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeReport(array $data, string $keywords, string $source): array
    {
        $videos = [];
        foreach ((array) ($data['videos'] ?? []) as $video) {
            if (! is_array($video)) {
                continue;
            }
            $title = trim((string) ($video['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $videos[] = [
                'title' => Str::limit($title, 80),
                'meta' => Str::limit(trim((string) ($video['meta'] ?? 'Technique guide')), 40),
            ];
        }

        $focus = trim((string) ($data['focus'] ?? ''));
        $wentWell = trim((string) ($data['went_well'] ?? $data['wentWell'] ?? ''));
        $needsWork = trim((string) ($data['needs_work'] ?? $data['needsWork'] ?? ''));
        $home = trim((string) ($data['home'] ?? $data['home_plan'] ?? ''));

        if ($focus === '' || $wentWell === '' || $needsWork === '' || $home === '') {
            throw new RuntimeException('Generated report was incomplete. Please try again.');
        }

        return [
            'title' => Str::limit(trim((string) ($data['title'] ?? $keywords)), 80) ?: 'Session report',
            'summary' => Str::limit(trim((string) ($data['summary'] ?? '')), 280)
                ?: Str::limit($focus, 180),
            'focus' => $focus,
            'went_well' => $wentWell,
            'needs_work' => $needsWork,
            'home' => $home,
            'videos' => array_slice($videos, 0, 3),
            'source' => $source,
        ];
    }

    /**
     * Professional offline draft when Ollama is unavailable.
     *
     * @param  array{player_name?: string, sport?: string, age?: string}  $context
     * @return array<string, mixed>
     */
    private function professionalFallback(string $keywords, array $context): array
    {
        $player = trim((string) ($context['player_name'] ?? 'the player'));
        $topic = trim($keywords);

        return [
            'title' => Str::title($topic),
            'summary' => "Today’s session with {$player} centred on {$topic}. The draft below gives a clear focus for the week, strengths to reinforce, and a practical home plan.",
            'focus' => "This week, prioritise {$topic} in every training block. Keep cues short and measurable — for example, complete 3 quality reps before increasing speed or pressure. Review one short clip mid-week and adjust one detail only.",
            'went_well' => "{$player} showed strong engagement and a willingness to apply coaching cues related to {$topic}. Effort levels stayed high, and there were clear moments of improvement when the task was broken into smaller steps.",
            'needs_work' => "Consistency under light pressure is the next step. When the tempo rises, technique around {$topic} can rush. Slow the first touch/decision, then accelerate — quality first, then speed.",
            'home' => "1) Technical reps — 12–15 minutes on {$topic}, 3× this week, focusing on clean form over volume.\n2) Constraint game — add one simple rule (e.g. two-touch max or scan before receive) for 8 minutes.\n3) Reflection — record a 20–30 second clip once and note one thing that improved and one cue for next session.",
            'videos' => [],
            'source' => 'fallback',
        ];
    }
}
