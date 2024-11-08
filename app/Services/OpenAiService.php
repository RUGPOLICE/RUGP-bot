<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiService
{
    const BaseURL = 'https://api.openai.com/v1';

    public function post(string $endpoint, array $data = []): Response
    {
        $key = config('services.openai.key');
        return Http::withHeader('Authorization', "Bearer $key")
            ->post(self::BaseURL . $endpoint, array_merge($data, ['model' => 'gpt-4o']));
    }

    public function getChatCompletion(string $prompt, string $name, array $ticker): string
    {
        $response = $this->post('/chat/completions', [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => __('gpt.system', [
                        'name' => $name,
                        'ticker' => $ticker ? __('gpt.ticker', $ticker) : __('gpt.ticker_not_found'),
                    ]),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ]
        ]);

        if ($response->status() !== 200) {

            $this->setOpenaiAvailable(false);
            return __('gpt.error');

        }

        $response = $response->json();
        if (!isset($response['choices'])) {

            $this->setOpenaiAvailable(false);
            return __('gpt.error');

        }

        $this->setOpenaiAvailable();
        return $response['choices'][0]['message']['content'];
    }

    public function isOpenaiAvailable(): bool
    {
        return Cache::rememberForever('openai-status', fn () => true);
    }

    public function setOpenaiAvailable(bool $available = true): bool
    {
        return Cache::set('openai-status', $available, 60 * 60 * 24 * 30);
    }
}
