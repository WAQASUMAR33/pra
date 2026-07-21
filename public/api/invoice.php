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

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM Invoice WHERE id = ?");
        $stmt->execute([$id]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(["error" => "Invoice not found"]);
            exit;
        }

        // Fetch invoice items
        $stmt = $pdo->prepare("SELECT * FROM InvoiceItem WHERE invoiceId = ?");
        $stmt->execute([$id]);
        $invoice['items'] = $stmt->fetchAll();

        // Format numeric values
        $invoice['invoiceType'] = (int)$invoice['invoiceType'];
        $invoice['totalQuantity'] = (int)$invoice['totalQuantity'];
        $invoice['paymentMode'] = (int)$invoice['paymentMode'];
        $invoice['numberOfGuests'] = $invoice['numberOfGuests'] !== null ? (int)$invoice['numberOfGuests'] : null;

        echo json_encode($invoice);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($method === 'DELETE') {
    try {
        $pdo->beginTransaction();

        // Delete items
        $stmt = $pdo->prepare("DELETE FROM InvoiceItem WHERE invoiceId = ?");
        $stmt->execute([$id]);

        // Delete invoice
        $stmt = $pdo->prepare("DELETE FROM Invoice WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Invoice deleted successfully"]);
    } catch (PDOException $e) {
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
