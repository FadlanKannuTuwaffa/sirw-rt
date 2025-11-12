<?php

namespace App\Http\Controllers\Resident;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\View\View;
use Dompdf\Dompdf;
use Dompdf\Options;

class ReceiptController extends Controller
{
    public function show(Request $request, Bill $bill): View
    {
        $this->authorizeBill($bill);
        $payment = $bill->payments()->where('status', 'paid')->latest('paid_at')->firstOrFail();

        return view('resident.receipt', [
            'bill' => $bill,
            'payment' => $payment,
            'exporting' => false,
        ]);
    }

    public function download(Request $request, Bill $bill)
    {
        $this->authorizeBill($bill);
        $payment = $bill->payments()->where('status', 'paid')->latest('paid_at')->firstOrFail();

        $html = ViewFacade::make('resident.receipt', [
            'bill' => $bill,
            'payment' => $payment,
            'exporting' => true,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'bukti-pembayaran-' . ($bill->invoice_number ?? $bill->id) . '.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, ['Content-Type' => 'application/pdf']);
    }

    private function authorizeBill(Bill $bill): void
    {
        if ($bill->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
