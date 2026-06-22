<?php

namespace App\Services;

use App\Models\SiteText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class SiteTextRepository
{
    /** @var Collection<string, SiteText>|null */
    private ?Collection $runtime = null;

    public function get(string $key): ?SiteText
    {
        return $this->allKeyed()->get($key);
    }

    public function value(string $key, ?string $default = null): ?string
    {
        $text = $this->get($key);
        $value = $text?->value;

        if ($value === null || trim($value) === '') {
            return $default;
        }

        return $value;
    }

    /**
     * @return Collection<string, SiteText>
     */
    public function allKeyed(): Collection
    {
        if ($this->runtime !== null) {
            return $this->runtime;
        }

        if (! Schema::hasTable('site_texts')) {
            $this->runtime = collect();

            return $this->runtime;
        }

        $this->runtime = SiteText::query()->get()->keyBy('key');

        return $this->runtime;
    }

    public function forget(): void
    {
        $this->runtime = null;
    }
}
