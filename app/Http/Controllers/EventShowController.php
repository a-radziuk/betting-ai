<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventShowCache;
use App\Support\PageSeo;
use Illuminate\View\View;

class EventShowController extends Controller
{
    public function __invoke(Event $event, EventShowCache $eventShowCache): View
    {
        $event->loadMissing('tournament');

        if ($event->tournament !== null && ! $event->tournament->is_active) {
            abort(404);
        }

        return view('event', [
            'mainHtml' => $eventShowCache->mainContentHtml($event),
            'seo' => PageSeo::forEventShow($event),
        ]);
    }
}
