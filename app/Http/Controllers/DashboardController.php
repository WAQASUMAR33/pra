<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('items')->orderBy('createdAt', 'desc')->take(50)->get();

        $totalSales = 0.0;
        $totalTax = 0.0;
        $successCount = 0;
        $pendingCount = 0;

        foreach ($invoices as $inv) {
            $totalSales += (float)$inv->totalBillAmount;
            $totalTax += (float)$inv->totalTaxCharged;
            if ($inv->status === 'SUCCESS') {
                $successCount++;
            } elseif ($inv->status === 'DRAFT' || $inv->status === 'FAILED') {
                $pendingCount++;
            }
        }

        $successRate = $invoices->count() > 0 
            ? round(($successCount / $invoices->count()) * 100)
            : 0;

        $stats = [
            'totalSales' => $totalSales,
            'totalTax' => $totalTax,
            'successRate' => $successRate,
            'pendingUploads' => $pendingCount
        ];

        return view('dashboard', compact('invoices', 'stats'));
    }
}
