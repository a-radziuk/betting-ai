<?php

namespace App\Http\Controllers;

use App\Models\LegalPage;
use Illuminate\View\View;

class LegalPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = LegalPage::query()
            ->where('slug', $slug)
            ->firstOrFail();

        return view('legal.show', [
            'page' => $page,
        ]);
    }
}
