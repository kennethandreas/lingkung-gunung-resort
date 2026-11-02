<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Kanal resmi resort (email, situs, media sosial) dari Pengaturan Umum.
 * Satu sumber untuk footer, halaman kontak, dan JSON-LD (sameAs) supaya tidak saling bertentangan.
 */
class ResortContact
{
    /** @return list<array{key: string, label: string, display: string, url: string, icon: string}> */
    public static function socials(): array
    {
        $links = [];

        if ($ig = Setting::get('contact_instagram')) {
            $links[] = ['key' => 'instagram', 'label' => 'Instagram', 'display' => '@'.ltrim($ig, '@'), 'url' => 'https://www.instagram.com/'.ltrim($ig, '@'), 'icon' => 'heroicon-o-camera'];
        }
        if ($fb = Setting::get('contact_facebook')) {
            $links[] = ['key' => 'facebook', 'label' => 'Facebook', 'display' => $fb, 'url' => 'https://www.facebook.com/'.$fb, 'icon' => 'heroicon-o-hand-thumb-up'];
        }
        if ($yt = Setting::get('contact_youtube')) {
            $links[] = ['key' => 'youtube', 'label' => 'YouTube', 'display' => $yt, 'url' => 'https://www.youtube.com/'.$yt, 'icon' => 'heroicon-o-play-circle'];
        }
        if ($web = Setting::get('contact_website')) {
            $links[] = ['key' => 'website', 'label' => 'Situs resmi', 'display' => preg_replace('#^https?://(www\.)?#', '', rtrim($web, '/')), 'url' => $web, 'icon' => 'heroicon-o-globe-alt'];
        }

        return $links;
    }

    /** URL profil resmi untuk schema.org sameAs. */
    public static function sameAs(): array
    {
        return array_values(array_map(fn ($link) => $link['url'], self::socials()));
    }

    public static function email(): ?string
    {
        return Setting::get('contact_email') ?: null;
    }
}
