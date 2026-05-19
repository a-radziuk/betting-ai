<?php

namespace App\Http\Controllers;

use App\Services\EventUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use JsonException;

class AdminUploadEventsController extends Controller
{
    public function show(): View
    {
        return view('admin.upload-events');
    }

    public function store(Request $request, EventUploadService $uploadService): RedirectResponse
    {
        $json = (string) $request->input('payload', '');

        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->redirectWithPayloadError($request, __('Invalid JSON.'));
        }

        if (! is_array($payload)) {
            return $this->redirectWithPayloadError($request, __('Invalid JSON.'));
        }

        $eventCount = $uploadService->import($payload);

        return redirect()
            ->route('admin.upload-events')
            ->with('status', __('Uploaded :count event(s).', ['count' => $eventCount]));
    }

    private function redirectWithPayloadError(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.upload-events')
            ->withErrors(['payload' => $message])
            ->withInput();
    }
}
