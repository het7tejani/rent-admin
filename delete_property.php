<?php
// Enable error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$dbHost = 'localhost';
$dbName = 'property';
$dbUser = 'buyhome_user';
$dbPass = 'hettejani1605';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $propertyId = $input['id'] ?? null;

    if (!$propertyId) {
        $response['message'] = 'Property ID is missing.';
        echo json_encode($response);
        exit();
    }

    try {
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Before deleting, you might want to fetch the file paths and delete the physical files
        // This example only deletes the database record
        $stmt = $pdo->prepare("DELETE FROM properties WHERE id = :id");
        $stmt->bindParam(':id', $propertyId, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $response['success'] = true;
            $response['message'] = 'Property deleted successfully.';
        } else {
            $response['message'] = 'Property not found or could not be deleted.';
        }

    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>