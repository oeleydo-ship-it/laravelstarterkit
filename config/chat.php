<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    |
    | Chat files are stored on a private disk and only ever served through the
    | download controllers, which re-check tenant ownership and the visitor
    | token. Do not point this at the "public" disk — that would make every
    | uploaded file reachable by URL alone.
    |
    */

    'attachments' => [
        'disk' => env('CHAT_ATTACHMENT_DISK', 'local'),
        'max_kb' => (int) env('CHAT_ATTACHMENT_MAX_KB', 10240),
        'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'csv', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Assist
    |--------------------------------------------------------------------------
    |
    | Drafting suggestions for agents. The "null" provider is the default so the
    | feature is inert until a workspace deliberately configures a real one.
    |
    */

    'ai' => [
        'provider' => env('CHAT_AI_PROVIDER', 'null'),
        'max_tokens' => (int) env('CHAT_AI_MAX_TOKENS', 8192),
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        ],
        'kimi' => [
            'key' => env('KIMI_API_KEY', env('MOONSHOT_API_KEY')),
            'model' => env('KIMI_MODEL', 'kimi-k3'),
            'base_url' => env('KIMI_BASE_URL', 'https://api.moonshot.ai/v1'),
        ],
        'anthropic' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-5'),
            'max_tokens' => (int) env('CHAT_AI_MAX_TOKENS', 8192),
            'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),
            'version' => '2023-06-01',
        ],
        // How many prior messages and knowledge base articles are handed to the
        // model as context for a suggestion.
        'history_limit' => 12,
        'article_limit' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Base Documents
    |--------------------------------------------------------------------------
    */

    'knowledge_base' => [
        'disk' => env('CHAT_KB_DISK', env('CHAT_ATTACHMENT_DISK', 'local')),
        'max_kb' => (int) env('CHAT_KB_MAX_KB', 10240),
        'extensions' => ['pdf', 'txt', 'md', 'csv', 'doc', 'docx'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Outbound Webhooks
    |--------------------------------------------------------------------------
    */

    'webhooks' => [
        'timeout' => 5,
    ],

];
