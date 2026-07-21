<?php
include_once 'db.php';

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Table names in Prisma default to model name (case-sensitive or matching model name)
        // Let's use `MerchantConfig` as defined in schema.prisma.
        $stmt = $pdo->prepare("SELECT * FROM MerchantConfig WHERE isActive = 1 LIMIT 1");
        $stmt->execute();
        $config = $stmt->fetch();
        
        if (!$config) {
            // Return defaults if none exist
            $config = [
                "posId" => "820816",
                "token" => "2D79A61F",
                "branchName" => "Lahore Main Branch",
                "branchAddress" => "Gulberg III, Lahore, Punjab",
                "apiUrl" => "https://ims.pral.com.pk/ims/sandbox/api/Live/PostData",
                "isActive" => true
            ];
        } else {
            $config['isActive'] = (bool)$config['isActive'];
        }
        
        echo json_encode($config);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} elseif ($method === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        $posId = $data['posId'] ?? null;
        $token = $data['token'] ?? null;
        $branchName = $data['branchName'] ?? null;
        $branchAddress = $data['branchAddress'] ?? null;
        $apiUrl = $data['apiUrl'] ?? "https://ims.pral.com.pk/ims/sandbox/api/Live/PostData";

        if (!$posId || !$token || !$branchName || !$branchAddress) {
            http_response_code(400);
            echo json_encode(["error" => "Missing required fields"]);
            exit;
        }

        // Deactivate all configurations
        $pdo->prepare("UPDATE MerchantConfig SET isActive = 0")->execute();

        // Create new active config
        $stmt = $pdo->prepare("INSERT INTO MerchantConfig (posId, token, branchName, branchAddress, apiUrl, isActive, createdAt, updatedAt) VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())");
        $stmt->execute([$posId, $token, $branchName, $branchAddress, $apiUrl]);
        
        $newId = $pdo->lastInsertId();
        
        $stmt = $pdo->prepare("SELECT * FROM MerchantConfig WHERE id = ?");
        $stmt->execute([$newId]);
        $config = $stmt->fetch();
        $config['isActive'] = (bool)$config['isActive'];

        echo json_encode(["success" => true, "config" => $config]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["error" => "Method Not Allowed"]);
}
?>
