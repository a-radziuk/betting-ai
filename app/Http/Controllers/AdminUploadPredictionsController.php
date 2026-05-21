<?php

namespace App\Http\Controllers;

use App\Services\EventPredictionImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use JsonException;

class AdminUploadPredictionsController extends Controller
{
    public function show(): View
    {
        return view('admin.upload-predictions');
    }

    public function store(Request $request, EventPredictionImportService $importService): RedirectResponse
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
            $result = $importService->importList($decoded);
        } catch (InvalidArgumentException $e) {
            return $this->redirectWithPayloadError($e->getMessage());
        }

        return redirect()
            ->route('admin.upload-predictions')
            ->with(
                'status',
                __('Imported :imported prediction(s); skipped :skipped.', [
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                ])
            );
    }

    private function redirectWithPayloadError(string $message): RedirectResponse
    {
        return redirect()
            ->route('admin.upload-predictions')
            ->withErrors(['payload' => $message])
            ->withInput();
    }
}
