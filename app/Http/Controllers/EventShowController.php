<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventShowCache;
use Illuminate\View\View;

class EventShowController extends Controller
{
    public function __invoke(Event $event, EventShowCache $eventShowCache): View
    {
        return view('event', [
            'mainHtml' => $eventShowCache->mainContentHtml($event),
        ]);
    }
}
