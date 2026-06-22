<?php

if (! function_exists('app_name')) {
    function app_name(): string
    {
        return (string) config('app.name');
    }
}

if (! function_exists('app_brand')) {
    /**
     * @param  array<string, string>  $replace
     */
    function app_brand(string $key, array $replace = []): string
    {
        return __($key, ['app' => app_name()] + $replace);
    }
}

if (! function_exists('app_page_title')) {
    /**
     * @param  array<string, string>  $replace
     */
    function app_page_title(string $suffix, array $replace = []): string
    {
        return app_brand(':app | '.$suffix, $replace);
    }
}

if (! function_exists('feature')) {
    /**
     * Whether a named feature flag is enabled (from config/features.php, backed by .env).
     */
    function feature(string $name): bool
    {
        $flags = config('features', []);

        if (! array_key_exists($name, $flags)) {
            return false;
        }

        return (bool) $flags[$name];
    }
}

if (! function_exists('site_text')) {
    /**
     * @param  array<string, string>  $replace
     */
    function site_text(string $key, array $replace = [], ?string $default = null): string
    {
        $value = app(\App\Services\SiteTextRepository::class)->value($key, $default);

        if ($value === null) {
            $value = $default ?? $key;
        }

        foreach (['app' => app_name()] + $replace as $name => $replacement) {
            $value = str_replace(':'.$name, (string) $replacement, $value);
        }

        return $value;
    }
}
