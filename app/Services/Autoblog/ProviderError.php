<?php
namespace App\Services\Autoblog;
use Throwable;
class ProviderError {
    public static function friendly(Throwable|string $error): string {
        $message=$error instanceof Throwable ? $error->getMessage() : $error;
        $lower=strtolower($message);
        if (str_contains($lower,'curl error') || str_contains($lower,"couldn't connect") || str_contains($lower,'connection') || str_contains($lower,'could not be reached')) return 'The AI provider could not be reached. Check this server’s internet/firewall access and the Base URL in AI Settings, then try again.';
        if (str_contains($lower,'401') || str_contains($lower,'403') || str_contains($lower,'authentication')) return 'The AI provider rejected the credentials. Check the API key in AI Settings.';
        if (str_contains($lower,'429') || str_contains($lower,'rate limit')) return 'The AI provider rate limit or balance limit was reached. Check the provider account and try again shortly.';
        if (str_contains($lower,'token limit') || str_contains($lower,'finish_reason') || str_contains($lower,'exhausted')) return 'The AI provider ran out of output space before finishing the article. Retry generation or increase CHAT_AI_MAX_TOKENS.';
        if (str_contains($lower,'empty response') || str_contains($lower,'valid article json')) return 'The AI provider returned an unusable article. Please generate it again.';
        return 'Article generation failed. Check the AI provider, model, and Base URL settings, then try again.';
    }
}
