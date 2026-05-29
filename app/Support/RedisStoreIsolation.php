<?php

namespace App\Support;

final class RedisStoreIsolation
{
    /**
     * Redis connection name used by a cache store (e.g. "redis" store → "cache" connection).
     */
    public static function cacheStoreConnection(string $store): ?string
    {
        $driver = config("cache.stores.{$store}.driver");

        if ($driver !== 'redis') {
            return null;
        }

        $connection = config("cache.stores.{$store}.connection");

        return is_string($connection) && $connection !== '' ? $connection : 'cache';
    }

    /**
     * Redis DB index for a named redis connection, or null if not redis.
     */
    public static function connectionDatabase(string $connection): ?string
    {
        $database = config("database.redis.{$connection}.database");

        return $database !== null ? (string) $database : null;
    }

    /**
     * @return array{0: string, 1: string}|null Pair of connection names if session and cache share one Redis DB.
     */
    public static function sessionCacheConflict(): ?array
    {
        if (config('session.driver') !== 'redis') {
            return null;
        }

        $sessionConnection = config('session.connection');
        if (! is_string($sessionConnection) || $sessionConnection === '') {
            $sessionConnection = 'default';
        }

        $pageCacheStore = (string) config('page_cache.cache_store', 'redis');
        $cacheConnection = self::cacheStoreConnection($pageCacheStore);
        if ($cacheConnection === null) {
            return null;
        }

        $sessionDb = self::connectionDatabase($sessionConnection);
        $cacheDb = self::connectionDatabase($cacheConnection);

        if ($sessionDb === null || $cacheDb === null || $sessionDb !== $cacheDb) {
            return null;
        }

        return [$sessionConnection, $cacheConnection];
    }
}
