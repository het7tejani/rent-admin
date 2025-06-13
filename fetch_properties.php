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

$properties = [];
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT * FROM properties ORDER BY created_at DESC");
    $properties = $stmt->fetchAll();

    // Decode gallery_images JSON string back into an array for the frontend
    foreach ($properties as &$property) {
        if (!empty($property['gallery_images'])) {
            $property['gallery_images'] = json_decode($property['gallery_images']);
        } else {
            $property['gallery_images'] = [];
        }
    }

} catch (PDOException $e) {
    error_log('Database error fetching properties: ' . $e->getMessage());
    // In a real application, you might return an error message to the client here
}

echo json_encode($properties);
?>