<?php

namespace Tests\Feature\Chat;

use App\Services\Chat\Ai\AnthropicAiProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AnthropicAiProviderTest extends TestCase
{
    protected function provider(array $overrides = []): AnthropicAiProvider
    {
        return new AnthropicAiProvider(array_merge(config('chat.ai.anthropic'), array_merge([
            'key' => 'test-key',
            'model' => 'claude-sonnet-5',
        ], $overrides)));
    }

    public function test_it_is_unconfigured_without_a_key(): void
    {
        $this->assertFalse($this->provider(['key' => null])->isConfigured());
        $this->assertTrue($this->provider()->isConfigured());
    }

    public function test_it_sends_the_prompt_and_returns_the_text(): void
    {
        Http::fake([
            '*/v1/messages' => Http::response([
                'content' => [
                    ['type' => 'text', 'text' => 'Refunds take '],
                    ['type' => 'text', 'text' => 'five days.'],
                ],
            ]),
        ]);

        $reply = $this->provider()->complete('You are support.', [
            ['role' => 'user', 'content' => 'How long do refunds take?'],
        ]);

        // Several text blocks are one reply, not the first one only.
        $this->assertEquals('Refunds take five days.', $reply);

        Http::assertSent(function ($request) {
            return $request->hasHeader('x-api-key', 'test-key')
                && $request['system'] === 'You are support.'
                && $request['messages'][0]['content'] === 'How long do refunds take?';
        });
    }

    public function test_an_upstream_error_becomes_an_exception(): void
    {
        Http::fake(['*/v1/messages' => Http::response(['error' => 'nope'], 429)]);

        $this->expectException(RuntimeException::class);

        $this->provider()->complete('system', [['role' => 'user', 'content' => 'hi']]);
    }

    public function test_an_empty_response_becomes_an_exception(): void
    {
        Http::fake(['*/v1/messages' => Http::response(['content' => []])]);

        $this->expectException(RuntimeException::class);

        $this->provider()->complete('system', [['role' => 'user', 'content' => 'hi']]);
    }

    public function test_it_refuses_to_call_out_when_unconfigured(): void
    {
        Http::fake();

        try {
            $this->provider(['key' => null])->complete('system', [['role' => 'user', 'content' => 'hi']]);
            $this->fail('Expected a RuntimeException.');
        } catch (RuntimeException) {
            Http::assertNothingSent();
        }
    }
}
