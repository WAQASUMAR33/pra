<?php
include_once 'db.php';

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

function guidv4() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

if ($method === 'GET') {
    try {
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        
        // Fetch invoices
        $stmt = $pdo->prepare("SELECT * FROM Invoice ORDER BY createdAt DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $invoices = $stmt->fetchAll();
        
        // Fetch items for each invoice
        foreach ($invoices as &$invoice) {
            $stmt = $pdo->prepare("SELECT * FROM InvoiceItem WHERE invoiceId = ?");
            $stmt->execute([$invoice['id']]);
            $invoice['items'] = $stmt->fetchAll();
            
            // Format numeric types
            $invoice['invoiceType'] = (int)$invoice['invoiceType'];
            $invoice['totalQuantity'] = (int)$invoice['totalQuantity'];
            $invoice['paymentMode'] = (int)$invoice['paymentMode'];
            $invoice['numberOfGuests'] = $invoice['numberOfGuests'] !== null ? (int)$invoice['numberOfGuests'] : null;
        }
        
        echo json_encode($invoices);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
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
            http_response_code(400);
            echo json_encode(["error" => "Invoice must have at least one item."]);
            exit;
        }

        // Get active config
        $stmt = $pdo->prepare("SELECT posId FROM MerchantConfig WHERE isActive = 1 LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        $posId = $config ? $config['posId'] : "820816";

        // Generate invoice number and USIN
        $timestamp = round(microtime(true) * 1000);
        $rand = random_int(1000, 9999);
        $usin = $posId . "-" . $timestamp . "-" . $rand;
        $invoiceNumber = "INV-" . substr((string)$timestamp, -6) . "-" . substr((string)$rand, -3);
        
        $invoiceId = guidv4();

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
                "id" => guidv4(),
                "invoiceId" => $invoiceId,
                "itemCode" => $itemCode,
                "itemName" => $item['itemName'] ?? "",
                "quantity" => $qty,
                "pctCode" => $item['pctCode'] ?? "00000000",
                "taxRate" => $rate,
                "saleValue" => $saleValue,
                "salesTaxApplicable" => $taxAmount,
                "furtherTax" => 0.0,
                "federalTax" => 0.0,
                "discount" => $discount,
                "netAmount" => $netAmount
            ];
        }

        $totalBillAmount = $totalSaleValue - $totalDiscount + $totalTaxCharged;

        // Begin transaction
        $pdo->beginTransaction();

        $eventDateFormatted = $eventDate ? date("Y-m-d H:i:s", strtotime($eventDate)) : null;

        // Insert Invoice
        $stmt = $pdo->prepare("INSERT INTO Invoice 
            (id, invoiceNumber, posId, usin, dateTime, buyerNtn, buyerCnic, buyerName, buyerPhone, invoiceType, 
             totalQuantity, totalSaleValue, totalTaxCharged, totalDiscount, totalBillAmount, paymentMode, 
             status, eventType, eventDate, numberOfGuests, createdAt, updatedAt) 
            VALUES 
            (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'DRAFT', ?, ?, ?, NOW(), NOW())");
        
        $stmt->execute([
            $invoiceId, $invoiceNumber, $posId, $usin, $buyerNtn, $buyerCnic, $buyerName, $buyerPhone, $invoiceType,
            $totalQuantity, $totalSaleValue, $totalTaxCharged, $totalDiscount, $totalBillAmount, $paymentMode,
            $eventType, $eventDateFormatted, $numberOfGuests
        ]);

        // Insert Items
        $itemStmt = $pdo->prepare("INSERT INTO InvoiceItem 
            (id, invoiceId, itemCode, itemName, quantity, pctCode, taxRate, saleValue, salesTaxApplicable, 
             furtherTax, federalTax, discount, netAmount) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        foreach ($computedItems as $cItem) {
            $itemStmt->execute([
                $cItem['id'], $cItem['invoiceId'], $cItem['itemCode'], $cItem['itemName'], $cItem['quantity'],
                $cItem['pctCode'], $cItem['taxRate'], $cItem['saleValue'], $cItem['salesTaxApplicable'],
                $cItem['furtherTax'], $cItem['federalTax'], $cItem['discount'], $cItem['netAmount']
            ]);
        }

        $pdo->commit();

        // Retrieve created invoice
        $stmt = $pdo->prepare("SELECT * FROM Invoice WHERE id = ?");
        $stmt->execute([$invoiceId]);
        $invoice = $stmt->fetch();
        $invoice['items'] = $computedItems;

        echo json_encode(["success" => true, "invoice" => $invoice]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
}
?>
