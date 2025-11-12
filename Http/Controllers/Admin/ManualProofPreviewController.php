<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManualProofPreviewController extends Controller
{
    public function __invoke(Request $request, Payment $payment)
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && $payment->user_id !== $user->id)) {
            abort(403);
        }

        $path = ltrim((string) $payment->manual_proof_path, '/');
        $path = str_replace(['../', '..\\'], '', $path);

        if ($path === '') {
            abort(404);
        }

        $filename = basename($path);

        if (Storage::disk('private')->exists($path)) {
            return Storage::disk('private')->response($path, $filename);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->response($path, $filename);
        }

        abort(404);
    }
}
