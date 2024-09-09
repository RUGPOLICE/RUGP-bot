<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenAiService
{
    const BaseURL = 'https://api.openai.com/v1';

    public function post(string $endpoint, array $data = []): Response
    {
        $key = config('services.openai.key');
        return Http::withHeader('Authorization', "Bearer $key")
            ->post(self::BaseURL . $endpoint, array_merge($data, ['model' => 'gpt-4o-mini']));
    }

    public function getChatCompletion(string $prompt): string
    {
        $response = $this->post('/chat/completions', ['messages' => [['role' => 'user', 'content' => $prompt]]])->json();
        if (!isset($response['choices']))
            return __('telegram.text.gpt.error');

        return $response['choices'][0]['message']['content'];
    }
}
