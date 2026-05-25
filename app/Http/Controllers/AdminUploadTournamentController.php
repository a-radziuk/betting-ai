<?php

namespace App\Http\Controllers;

use App\Services\TournamentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use JsonException;

class AdminUploadTournamentController extends Controller
{
    public function show(): View
    {
        return view('admin.upload-tournament');
    }

    public function store(Request $request, TournamentImportService $importService): RedirectResponse
    {
        $json = (string) $request->input('payload', '');

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->redirectWithPayloadError(__('Invalid JSON.'));
        }

        if (! is_array($decoded)) {
            return $this->redirectWithPayloadError(__('Invalid JSON.'));
        }

        try {
            $result = $importService->import($decoded);
        } catch (InvalidArgumentException $e) {
            return $this->redirectWithPayloadError($e->getMessage());
        }

        return redirect()
            ->route('admin.upload-tournament')
            ->with('status', __('Uploaded tournament and :count team(s).', [
                'count' => $result['teams'],
            ]));
    }

    private function redirectWithPayloadError(string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.upload-tournament')
            ->withErrors(['payload' => $message])
            ->withInput();
    }
}
