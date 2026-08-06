<?php

namespace App\Support;

class Privileges
{
    public const CLIENTS_VIEW = 'clients.view';
    public const CLIENTS_MANAGE = 'clients.manage';
    public const TICKETS_VIEW = 'tickets.view';
    public const TICKETS_MANAGE = 'tickets.manage';
    public const CHAT_AGENT = 'chat.agent';
    public const CHAT_MANAGE = 'chat.manage';
    public const EMAIL_VIEW = 'email.view';
    public const EMAIL_MANAGE = 'email.manage';
    public const ENGAGE_VIEW = 'engage.view';
    public const ENGAGE_MANAGE = 'engage.manage';
    public const FORMS_VIEW = 'forms.view';
    public const FORMS_MANAGE = 'forms.manage';
    public const REVIEWS_VIEW = 'reviews.view';
    public const REVIEWS_MANAGE = 'reviews.manage';
    public const BOOKINGS_VIEW = 'bookings.view';
    public const BOOKINGS_MANAGE = 'bookings.manage';
    public const TEAM_MANAGE = 'team.manage';
    public const MODULES_MANAGE = 'modules.manage';
    public const BILLING_MANAGE = 'billing.manage';
    public const SETTINGS_MANAGE = 'settings.manage';

    public static function all(): array
    {
        return [
            self::CLIENTS_VIEW => 'View clients',
            self::CLIENTS_MANAGE => 'Manage clients',
            self::TICKETS_VIEW => 'View tickets',
            self::TICKETS_MANAGE => 'Manage tickets',
            self::CHAT_AGENT => 'Live chat agent',
            self::CHAT_MANAGE => 'Manage live chat settings',
            self::EMAIL_VIEW => 'View email marketing',
            self::EMAIL_MANAGE => 'Manage email marketing',
            self::ENGAGE_VIEW => 'View engage campaigns',
            self::ENGAGE_MANAGE => 'Manage engage campaigns',
            self::FORMS_VIEW => 'View forms & surveys',
            self::FORMS_MANAGE => 'Manage forms & surveys',
            self::REVIEWS_VIEW => 'View reviews',
            self::REVIEWS_MANAGE => 'Manage reviews',
            self::BOOKINGS_VIEW => 'View bookings',
            self::BOOKINGS_MANAGE => 'Manage bookings',
            self::TEAM_MANAGE => 'Manage team & groups',
            self::MODULES_MANAGE => 'Manage modules',
            self::BILLING_MANAGE => 'Manage billing',
            self::SETTINGS_MANAGE => 'Manage workspace settings',
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function groups(): array
    {
        return [
            'Clients' => [self::CLIENTS_VIEW, self::CLIENTS_MANAGE],
            'Tickets' => [self::TICKETS_VIEW, self::TICKETS_MANAGE],
            'Live Chat' => [self::CHAT_AGENT, self::CHAT_MANAGE],
            'Email Marketing' => [self::EMAIL_VIEW, self::EMAIL_MANAGE],
            'Engage' => [self::ENGAGE_VIEW, self::ENGAGE_MANAGE],
            'Forms' => [self::FORMS_VIEW, self::FORMS_MANAGE],
            'Reviews' => [self::REVIEWS_VIEW, self::REVIEWS_MANAGE],
            'Bookings' => [self::BOOKINGS_VIEW, self::BOOKINGS_MANAGE],
            'Workspace' => [self::TEAM_MANAGE, self::MODULES_MANAGE, self::BILLING_MANAGE, self::SETTINGS_MANAGE],
        ];
    }

    public static function defaultsForRole(?string $role): array
    {
        return match ($role) {
            'owner' => self::keys(),
            'admin' => array_values(array_diff(self::keys(), [self::BILLING_MANAGE])),
            'member' => [
                self::CLIENTS_VIEW,
                self::TICKETS_VIEW,
                self::CHAT_AGENT,
                self::EMAIL_VIEW,
                self::ENGAGE_VIEW,
                self::FORMS_VIEW,
                self::REVIEWS_VIEW,
                self::BOOKINGS_VIEW,
            ],
            default => [],
        };
    }
}
