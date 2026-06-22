<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param  array{title?: string, description?: string|null, og_title?: string|null, og_description?: string|null}|null  $seo
     */
    public function __construct(
        public ?string $pageTitle = null,
        public ?array $seo = null,
    ) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
