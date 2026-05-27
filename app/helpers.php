<?php

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
