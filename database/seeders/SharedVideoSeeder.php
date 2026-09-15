<?php

namespace Database\Seeders;

use App\Models\Coach;
use App\Models\SharedVideo;
use App\Models\User;
use Illuminate\Database\Seeder;

class SharedVideoSeeder extends Seeder
{
    public function run(): void
    {
        $coach = Coach::query()->whereHas('user', fn ($q) => $q->where('email', 'coach@coachnow.test'))->first()
            ?? Coach::query()->first();
        $athlete = User::query()->where('email', 'player@coachnow.test')->first();

        if (! $coach || ! $athlete) {
            return;
        }

        $samples = [
            [
                'title' => 'Scan before receiving',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'description' => 'Head-up scanning cues before the ball arrives.',
                'duration_label' => '3-min technique guide',
                'skill_tag' => 'Scanning',
            ],
            [
                'title' => 'Back-foot first touch',
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'description' => 'Wall drill progression for a clean first touch.',
                'duration_label' => 'Wall drill progression',
                'skill_tag' => 'First touch',
            ],
        ];

        foreach ($samples as $sample) {
            SharedVideo::query()->updateOrCreate(
                [
                    'coach_id' => $coach->id,
                    'athlete_id' => $athlete->id,
                    'title' => $sample['title'],
                ],
                [
                    'athlete_name' => $athlete->name,
                    'url' => $sample['url'],
                    'description' => $sample['description'],
                    'duration_label' => $sample['duration_label'],
                    'skill_tag' => $sample['skill_tag'],
                ]
            );
        }
    }
}
