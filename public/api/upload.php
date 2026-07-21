<?php
include_once 'db.php';

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

$id = $_GET['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required invoice ID parameter."]);
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
    exit;
}

try {
    // 1. Fetch invoice
    $stmt = $pdo->prepare("SELECT * FROM Invoice WHERE id = ?");
    $stmt->execute([$id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        http_response_code(404);
        echo json_encode(["error" => "Invoice not found."]);
        exit;
    }

    // Fetch items
    $stmt = $pdo->prepare("SELECT * FROM InvoiceItem WHERE invoiceId = ?");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll();

    // 2. Fetch active configuration
    $stmt = $pdo->prepare("SELECT * FROM MerchantConfig WHERE isActive = 1 LIMIT 1");
    $stmt->execute();
    $config = $stmt->fetch();

    if (!$config) {
        throw new Exception("No active PRA POS configuration found. Please configure the settings first.");
    }

    // 3. Mark invoice as PENDING
    $stmt = $pdo->prepare("UPDATE Invoice SET status = 'PENDING', updatedAt = NOW() WHERE id = ?");
    $stmt->execute([$id]);

    // 4. Format payload
    $payload = [
        "InvoiceNumber" => $invoice['invoiceNumber'] ?: "",
        "POSID" => (int)$config['posId'],
        "USIN" => $invoice['usin'],
        "DateTime" => date("Y-m-d H:i:s", strtotime($invoice['dateTime'])),
        "BuyerNTN" => $invoice['buyerNtn'] ?: "",
        "BuyerCNIC" => $invoice['buyerCnic'] ?: "",
        "BuyerName" => $invoice['buyerName'] ?: "",
        "BuyerPhoneNumber" => $invoice['buyerPhone'] ?: "",
        "InvoiceType" => (int)$invoice['invoiceType'],
        "TotalQuantity" => (int)$invoice['totalQuantity'],
        "TotalSaleValue" => (float)$invoice['totalSaleValue'],
        "TotalTaxCharged" => (float)$invoice['totalTaxCharged'],
        "TotalDiscount" => (float)$invoice['totalDiscount'],
        "TotalBillAmount" => (float)$invoice['totalBillAmount'],
        "PaymentMode" => (int)$invoice['paymentMode'],
        "Items" => array_map(function($item) use ($invoice) {
            return [
                "ItemCode" => $item['itemCode'],
                "ItemName" => $item['itemName'],
                "Quantity" => (int)$item['quantity'],
                "PCTCode" => $item['pctCode'] ?: "00000000",
                "TaxRate" => (float)$item['taxRate'],
                "SaleValue" => (float)$item['saleValue'],
                "SalesTaxApplicable" => (float)$item['salesTaxApplicable'],
                "FurtherTax" => (float)$item['furtherTax'],
                "FederalTax" => (float)$item['federalTax'],
                "Discount" => (float)$item['discount'],
                "InvoiceType" => (int)$invoice['invoiceType']
            ];
        }, $items)
    ];

    // 5. Check if it's in sandbox/mock simulated mode
    $token = $config['token'];
    $isSimulated = !$token || 
                   in_array(strtolower($token), ['sandbox', 'mock']) || 
                   strpos(strtolower($token), 'demo') !== false;

    $responseData = null;

    if ($isSimulated) {
        // Wait for 800ms to simulate network latency
        usleep(800000);

        $yyyymmdd = date('Ymd');
        $mockFiscalNo = "PRA-" . $config['posId'] . "-" . $yyyymmdd . "-" . substr($invoice['usin'], -6);

        $responseData = [
            "ResponseCode" => "00",
            "ResponseMsg" => "SUCCESS (Simulated Sandbox Response)",
            "InvoiceNumber" => $mockFiscalNo,
            "USIN" => $invoice['usin'],
            "QRCode" => "https://e.pra.punjab.gov.pk/verify?fiscalNumber=" . $mockFiscalNo . "&usin=" . $invoice['usin'] . "&posid=" . $config['posId']
        ];
    } else {
        // Real API request via cURL
        $ch = curl_init($config['apiUrl']);
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
            throw new Exception("cURL Error: " . curl_error($ch));
        }
        curl_close($ch);

        if ($httpCode >= 400) {
            throw new Exception("PRA Endpoint returned HTTP status: " . $httpCode);
        }

        $responseData = json_decode($response, true);
        if (!$responseData) {
            throw new Exception("PRA Endpoint returned invalid JSON response: " . $response);
        }
    }

    // 6. Update invoice status
    $isSuccess = isset($responseData['ResponseCode']) && $responseData['ResponseCode'] === "00";
    $status = $isSuccess ? 'SUCCESS' : 'FAILED';

    $praFiscalNumber = $responseData['InvoiceNumber'] ?? null;
    $praResponseCode = $responseData['ResponseCode'] ?? null;
    $praResponseMsg = $responseData['ResponseMsg'] ?? json_encode($responseData);

    $stmt = $pdo->prepare("UPDATE Invoice SET 
        status = ?, 
        praFiscalNumber = ?, 
        praResponseCode = ?, 
        praResponseMsg = ?, 
        updatedAt = NOW() 
        WHERE id = ?");
    $stmt->execute([$status, $praFiscalNumber, $praResponseCode, $praResponseMsg, $id]);

    // Retrieve updated invoice
    $stmt = $pdo->prepare("SELECT * FROM Invoice WHERE id = ?");
    $stmt->execute([$id]);
    $updatedInvoice = $stmt->fetch();
    $updatedInvoice['items'] = $items;

    echo json_encode([
        "success" => $isSuccess,
        "invoice" => $updatedInvoice,
        "payload" => $payload,
        "response" => $responseData
    ]);

} catch (Exception $e) {
    // Mark invoice as failed in DB
    try {
        $stmt = $pdo->prepare("UPDATE Invoice SET status = 'FAILED', praResponseMsg = ?, updatedAt = NOW() WHERE id = ?");
        $stmt->execute([$e->getMessage(), $id]);
    } catch (PDOException $dbErr) {
        // Suppress DB update error to return original exception
    }

    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>
