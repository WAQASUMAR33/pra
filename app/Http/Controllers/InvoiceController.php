<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\MerchantConfig;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function create()
    {
        return view('invoices.new');
    }

    public function store(Request $request)
    {
        try {
            $data = $request->all();
            if ($request->isJson() || $request->wantsJson()) {
                $data = json_decode($request->getContent(), true) ?: $request->all();
            }

            $buyerNtn = $data['buyerNtn'] ?? null;
            $buyerCnic = $data['buyerCnic'] ?? null;
            $buyerName = $data['buyerName'] ?? null;
            $buyerPhone = $data['buyerPhone'] ?? null;
            $invoiceType = isset($data['invoiceType']) ? (int)$data['invoiceType'] : 1;
            $paymentMode = isset($data['paymentMode']) ? (int)$data['paymentMode'] : 1;
            $items = $data['items'] ?? [];
            $totalDiscount = isset($data['totalDiscount']) ? (float)$data['totalDiscount'] : 0.0;
            $eventType = $data['eventType'] ?? null;
            $eventDate = $data['eventDate'] ?? null;
            $numberOfGuests = isset($data['numberOfGuests']) && $data['numberOfGuests'] !== '' ? (int)$data['numberOfGuests'] : null;

            if (empty($items)) {
                return response()->json(['error' => 'Invoice must have at least one item.'], 400);
            }

            // Get active config
            $config = MerchantConfig::where('isActive', true)->first();
            $posId = $config ? $config->posId : "820816";

            // Generate invoice number and USIN
            $timestamp = round(microtime(true) * 1000);
            $rand = random_int(1000, 9999);
            $usin = $posId . "-" . $timestamp . "-" . $rand;
            $invoiceNumber = "INV-" . substr((string)$timestamp, -6) . "-" . substr((string)$rand, -3);

            // Calculate totals
            $totalQuantity = 0;
            $totalSaleValue = 0.0;
            $totalTaxCharged = 0.0;
            $computedItems = [];

            foreach ($items as $item) {
                $qty = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                $rate = isset($item['taxRate']) ? (float)$item['taxRate'] : 0.0;
                $price = isset($item['price']) ? (float)$item['price'] : 0.0;
                $discount = isset($item['discount']) ? (float)$item['discount'] : 0.0;

                $saleValue = $qty * $price;
                $taxAmount = ($saleValue - $discount) * ($rate / 100.0);
                $netAmount = $saleValue - $discount + $taxAmount;

                $totalQuantity += $qty;
                $totalSaleValue += $saleValue;
                $totalTaxCharged += $taxAmount;

                $itemCode = $item['itemCode'] ?? ("ITM-" . random_int(100, 999));

                $computedItems[] = [
                    'itemCode' => $itemCode,
                    'itemName' => $item['itemName'] ?? "",
                    'quantity' => $qty,
                    'pctCode' => $item['pctCode'] ?? "00000000",
                    'taxRate' => $rate,
                    'saleValue' => $saleValue,
                    'salesTaxApplicable' => $taxAmount,
                    'furtherTax' => 0.0,
                    'federalTax' => 0.0,
                    'discount' => $discount,
                    'netAmount' => $netAmount
                ];
            }

            $totalBillAmount = $totalSaleValue - $totalDiscount + $totalTaxCharged;

            \DB::beginTransaction();

            $eventDateFormatted = $eventDate ? date("Y-m-d H:i:s", strtotime($eventDate)) : null;

            // Create Invoice
            $invoice = Invoice::create([
                'invoiceNumber' => $invoiceNumber,
                'posId' => $posId,
                'usin' => $usin,
                'buyerNtn' => $buyerNtn,
                'buyerCnic' => $buyerCnic,
                'buyerName' => $buyerName,
                'buyerPhone' => $buyerPhone,
                'invoiceType' => $invoiceType,
                'totalQuantity' => $totalQuantity,
                'totalSaleValue' => $totalSaleValue,
                'totalTaxCharged' => $totalTaxCharged,
                'totalDiscount' => $totalDiscount,
                'totalBillAmount' => $totalBillAmount,
                'paymentMode' => $paymentMode,
                'status' => 'DRAFT',
                'eventType' => $eventType,
                'eventDate' => $eventDateFormatted,
                'numberOfGuests' => $numberOfGuests,
                'dateTime' => now()
            ]);

            // Save Items
            foreach ($computedItems as $cItem) {
                $invoice->items()->create($cItem);
            }

            \DB::commit();

            return response()->json(['success' => true, 'invoice' => $invoice->fresh('items')]);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $invoice = Invoice::with('items')->findOrFail($id);
        $config = MerchantConfig::where('isActive', true)->first();

        if (!$config) {
            $config = new MerchantConfig([
                'posId' => '820816',
                'token' => '2D79A61F',
                'branchName' => 'Lahore Main Branch',
                'branchAddress' => 'Gulberg III, Lahore, Punjab',
                'apiUrl' => 'https://ims.pral.com.pk/ims/sandbox/api/Live/PostData',
                'isActive' => true
            ]);
        }

        return view('invoices.show', compact('invoice', 'config'));
    }

    public function destroy($id)
    {
        try {
            $invoice = Invoice::findOrFail($id);
            $invoice->delete(); // Cascading delete will handle items if DB FK is set, or Eloquent model events
            
            return response()->json(['success' => true, 'message' => 'Invoice deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function upload($id)
    {
        try {
            $invoice = Invoice::with('items')->findOrFail($id);
            $config = MerchantConfig::where('isActive', true)->first();
            
            if (!$config) {
                throw new \Exception("No active PRA POS configuration found. Please configure the settings first.");
            }

            // Mark invoice as PENDING
            $invoice->update(['status' => 'PENDING']);

            // Format payload
            $payload = [
                "InvoiceNumber" => $invoice->invoiceNumber ?: "",
                "POSID" => (int)$config->posId,
                "USIN" => $invoice->usin,
                "DateTime" => $invoice->dateTime->format('Y-m-d H:i:s'),
                "BuyerNTN" => $invoice->buyerNtn ?: "",
                "BuyerCNIC" => $invoice->buyerCnic ?: "",
                "BuyerName" => $invoice->buyerName ?: "",
                "BuyerPhoneNumber" => $invoice->buyerPhone ?: "",
                "InvoiceType" => (int)$invoice->invoiceType,
                "TotalQuantity" => (int)$invoice->totalQuantity,
                "TotalSaleValue" => (float)$invoice->totalSaleValue,
                "TotalTaxCharged" => (float)$invoice->totalTaxCharged,
                "TotalDiscount" => (float)$invoice->totalDiscount,
                "TotalBillAmount" => (float)$invoice->totalBillAmount,
                "PaymentMode" => (int)$invoice->paymentMode,
                "Items" => $invoice->items->map(function($item) use ($invoice) {
                    return [
                        "ItemCode" => $item->itemCode,
                        "ItemName" => $item->itemName,
                        "Quantity" => (int)$item->quantity,
                        "PCTCode" => $item->pctCode ?: "00000000",
                        "TaxRate" => (float)$item->taxRate,
                        "SaleValue" => (float)$item->saleValue,
                        "SalesTaxApplicable" => (float)$item->salesTaxApplicable,
                        "FurtherTax" => (float)$item->furtherTax,
                        "FederalTax" => (float)$item->federalTax,
                        "Discount" => (float)$item->discount,
                        "InvoiceType" => (int)$invoice->invoiceType
                    ];
                })->toArray()
            ];

            // Check if simulated
            $token = $config->token;
            $isSimulated = !$token || 
                           in_array(strtolower($token), ['sandbox', 'mock']) || 
                           strpos(strtolower($token), 'demo') !== false;

            if ($isSimulated) {
                // Wait for 800ms
                usleep(800000);
                
                $yyyymmdd = date('Ymd');
                $mockFiscalNo = "PRA-" . $config->posId . "-" . $yyyymmdd . "-" . substr($invoice->usin, -6);

                $responseData = [
                    "ResponseCode" => "00",
                    "ResponseMsg" => "SUCCESS (Simulated Sandbox Response)",
                    "InvoiceNumber" => $mockFiscalNo,
                    "USIN" => $invoice->usin,
                    "QRCode" => "https://e.pra.punjab.gov.pk/verify?fiscalNumber=" . $mockFiscalNo . "&usin=" . $invoice->usin . "&posid=" . $config->posId
                ];
            } else {
                // Real API request via cURL
                $ch = curl_init($config->apiUrl);
                $payloadJson = json_encode($payload);
                
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token
                ]);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                if (curl_errno($ch)) {
                    throw new \Exception("cURL Error: " . curl_error($ch));
                }
                curl_close($ch);

                if ($httpCode >= 400) {
                    throw new \Exception("PRA Endpoint returned HTTP status: " . $httpCode);
                }

                $responseData = json_decode($response, true);
                if (!$responseData) {
                    throw new \Exception("PRA Endpoint returned invalid JSON response: " . $response);
                }
            }

            $isSuccess = isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === "00";
            $status = $isSuccess ? 'SUCCESS' : 'FAILED';

            $praFiscalNumber = $responseData['InvoiceNumber'] ?? null;
            $praResponseCode = $responseData['ResponseCode'] ?? null;
            $praResponseMsg = $responseData['ResponseMsg'] ?? json_encode($responseData);

            $invoice->update([
                'status' => $status,
                'praFiscalNumber' => $praFiscalNumber,
                'praResponseCode' => $praResponseCode,
                'praResponseMsg' => $praResponseMsg
            ]);

            return response()->json([
                'success' => $isSuccess,
                'invoice' => $invoice->fresh('items'),
                'payload' => $payload,
                'response' => $responseData
            ]);

        } catch (\Exception $e) {
            if (isset($invoice)) {
                $invoice->update([
                    'status' => 'FAILED',
                    'praResponseMsg' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
