<?php

namespace App\Http\Controllers;

use App\Support\FaqPageContent;
use App\Support\LegalPageContent;
use App\Support\PageSeo;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __invoke(): View
    {
        $page = FaqPageContent::page();

        abort_if($page === null, 404);

        return view('legal.show', [
            'page' => $page,
            'renderedContent' => LegalPageContent::render($page->content),
            'seo' => PageSeo::forLegalPage($page),
        ]);
    }
}
