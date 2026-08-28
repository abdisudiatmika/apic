<?php

namespace App\Support;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Filament's built-in UiAvatarsProvider sends every logged-in user's name to
 * ui-avatars.com as a plaintext query string (an external leak of employee PII)
 * and is blocked outright by our img-src CSP (docker/nginx/default.conf), which
 * only allows 'self' and data:. This renders the identical initials-on-circle
 * avatar as an inline SVG data URI instead, so nothing ever leaves the server.
 */
class LocalInitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn (string $segment): string => filled($segment) ? mb_substr($segment, 0, 1) : '')
            ->join('');

        $initials = mb_strtoupper(mb_substr($initials, 0, 2));

        $background = Color::convertToHex(FilamentColor::getColor('gray')[950] ?? Color::Gray[950]);

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                <rect width="64" height="64" rx="32" fill="{$background}" />
                <text x="32" y="32" fill="#ffffff" font-family="sans-serif" font-size="24" font-weight="600" text-anchor="middle" dominant-baseline="central">{$initials}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
