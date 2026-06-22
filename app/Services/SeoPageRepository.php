<?php

namespace App\Services;

use App\Models\SeoPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class SeoPageRepository
{
    /** @var Collection<string, SeoPage>|null */
    private ?Collection $runtime = null;

    public function get(string $key): ?SeoPage
    {
        return $this->allKeyed()->get($key);
    }

    /**
     * @return Collection<string, SeoPage>
     */
    public function allKeyed(): Collection
    {
        if ($this->runtime !== null) {
            return $this->runtime;
        }

        if (! Schema::hasTable('seo_pages')) {
            $this->runtime = collect();

            return $this->runtime;
        }

        $this->runtime = SeoPage::query()->orderBy('label')->get()->keyBy('key');

        return $this->runtime;
    }

    public function forget(): void
    {
        $this->runtime = null;
    }
}
