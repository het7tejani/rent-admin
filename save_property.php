<?php
// Enable error reporting for development (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Database connection details
$dbHost = 'localhost'; // Your database host
$dbName = 'property'; // The database name you specified
$dbUser = 'buyhome_user'; // Your database username
$dbPass = 'hettejani1605'; // Your database password

// Define upload directory
$uploadDir = 'uploads/'; // Make sure this directory exists and is writable

// Create the upload directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Establish database connection
        $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Function to sanitize input data
        function sanitizeInput($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;
        }

        // Collect and sanitize form data
        $propertyName = sanitizeInput($_POST['propertyName'] ?? '');
        $propertyAddress = sanitizeInput($_POST['propertyAddress'] ?? '');
        $propertyPrice = sanitizeInput($_POST['propertyPrice'] ?? '');
        $propertyType = sanitizeInput($_POST['propertyType'] ?? '');
        $propertyBeds = sanitizeInput($_POST['propertyBeds'] ?? '');
        $propertyBaths = sanitizeInput($_POST['propertyBaths'] ?? '');
        $propertySqft = sanitizeInput($_POST['propertySqft'] ?? '');
        $superBuiltUpAreaSqft = sanitizeInput($_POST['superBuiltUpAreaSqft'] ?? '');
        $carpetAreaSqft = sanitizeInput($_POST['carpetAreaSqft'] ?? '');
        $propertyDescription = sanitizeInput($_POST['propertyDescription'] ?? '');
        $keyFeatures = sanitizeInput($_POST['keyFeatures'] ?? '');
        $furnishing = sanitizeInput($_POST['furnishing'] ?? '');
        $bachelorsAllowed = sanitizeInput($_POST['bachelorsAllowed'] ?? '');
        $floorNo = sanitizeInput($_POST['floorNo'] ?? '');
        $totalFloors = sanitizeInput($_POST['totalFloors'] ?? '');
        $carParking = sanitizeInput($_POST['carParking'] ?? '');
        $listedBy = sanitizeInput($_POST['listedBy'] ?? '');
        $facing = sanitizeInput($_POST['facing'] ?? '');
        $maintenanceMonthly = sanitizeInput($_POST['maintenanceMonthly'] ?? '');
        $projectName = sanitizeInput($_POST['projectName'] ?? '');

        // Validate required fields
        if (empty($propertyName) || empty($propertyAddress) || empty($propertyPrice) ||
            empty($propertyType) || empty($propertyBeds) || empty($propertyBaths) ||
            empty($propertySqft) || empty($propertyDescription)) {
            throw new Exception('Please fill in all required fields (marked with *).');
        }

        // File upload handling for main image
        $mainImagePath = '';
        if (isset($_FILES['propertyMainImageFile']) && $_FILES['propertyMainImageFile']['error'] === UPLOAD_ERR_OK) {
            $mainImageFile = $_FILES['propertyMainImageFile'];
            $mainImageFileName = uniqid() . '_' . basename($mainImageFile['name']);
            $mainImageDestination = $uploadDir . $mainImageFileName;
            if (!move_uploaded_file($mainImageFile['tmp_name'], $mainImageDestination)) {
                throw new Exception('Failed to upload main image file.');
            }
            $mainImagePath = $mainImageDestination;
        } elseif (!empty($_POST['propertyMainImageURL'])) {
            $mainImagePath = sanitizeInput($_POST['propertyMainImageURL']);
        } else {
            throw new Exception('Main image is required. Please upload a file or provide a URL.');
        }

        // File upload handling for gallery images
        $galleryImagePaths = [];
        if (isset($_FILES['propertyGalleryImageFiles'])) {
            foreach ($_FILES['propertyGalleryImageFiles']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['propertyGalleryImageFiles']['error'][$key] === UPLOAD_ERR_OK) {
                    $galleryFileName = uniqid() . '_' . basename($_FILES['propertyGalleryImageFiles']['name'][$key]);
                    $galleryDestination = $uploadDir . $galleryFileName;
                    if (move_uploaded_file($tmp_name, $galleryDestination)) {
                        $galleryImagePaths[] = $galleryDestination;
                    } else {
                        // Log error but don't stop the process for individual gallery images
                        error_log("Failed to upload gallery image file: " . $_FILES['propertyGalleryImageFiles']['name'][$key]);
                    }
                }
            }
        }
        if (!empty($_POST['propertyGalleryImageURLs'])) {
            $urlList = explode(',', $_POST['propertyGalleryImageURLs']);
            foreach ($urlList as $url) {
                $trimmedUrl = trim($url);
                if (!empty($trimmedUrl)) {
                    $galleryImagePaths[] = sanitizeInput($trimmedUrl);
                }
            }
        }
        $galleryImagesJson = json_encode($galleryImagePaths); // Store as JSON string

        // File upload handling for video
        $videoPath = '';
        if (isset($_FILES['propertyVideoFile']) && $_FILES['propertyVideoFile']['error'] === UPLOAD_ERR_OK) {
            $videoFile = $_FILES['propertyVideoFile'];
            $videoFileName = uniqid() . '_' . basename($videoFile['name']);
            $videoDestination = $uploadDir . $videoFileName;
            if (!move_uploaded_file($videoFile['tmp_name'], $videoDestination)) {
                throw new Exception('Failed to upload video file.');
            }
            $videoPath = $videoDestination;
        } elseif (!empty($_POST['propertyVideoURL'])) {
            $videoPath = sanitizeInput($_POST['propertyVideoURL']);
        }

        // Prepare SQL INSERT statement
        $stmt = $pdo->prepare("INSERT INTO properties (
                property_name, address, price, type, bedrooms, bathrooms, total_sqft,
                super_built_up_area_sqft, carpet_area_sqft, main_image, gallery_images,
                video_url, description, key_features, furnishing, bachelors_allowed,
                floor_no, total_floors, car_parking, listed_by, facing, maintenance_monthly,
                project_name
            ) VALUES (
                :propertyName, :address, :price, :type, :bedrooms, :bathrooms, :totalSqft,
                :superBuiltUpAreaSqft, :carpetAreaSqft, :mainImage, :galleryImages,
                :videoUrl, :description, :keyFeatures, :furnishing, :bachelorsAllowed,
                :floorNo, :totalFloors, :carParking, :listedBy, :facing, :maintenanceMonthly,
                :projectName
            )");

        // Bind parameters
        $stmt->bindParam(':propertyName', $propertyName);
        $stmt->bindParam(':address', $propertyAddress);
        $stmt->bindParam(':price', $propertyPrice);
        $stmt->bindParam(':type', $propertyType);
        $stmt->bindParam(':bedrooms', $propertyBeds);
        $stmt->bindParam(':bathrooms', $propertyBaths);
        $stmt->bindParam(':totalSqft', $propertySqft);
        $stmt->bindParam(':superBuiltUpAreaSqft', $superBuiltUpAreaSqft);
        $stmt->bindParam(':carpetAreaSqft', $carpetAreaSqft);
        $stmt->bindParam(':mainImage', $mainImagePath);
        $stmt->bindParam(':galleryImages', $galleryImagesJson);
        $stmt->bindParam(':videoUrl', $videoPath);
        $stmt->bindParam(':description', $propertyDescription);
        $stmt->bindParam(':keyFeatures', $keyFeatures);
        $stmt->bindParam(':furnishing', $furnishing);
        $stmt->bindParam(':bachelorsAllowed', $bachelorsAllowed);
        $stmt->bindParam(':floorNo', $floorNo);
        $stmt->bindParam(':totalFloors', $totalFloors);
        $stmt->bindParam(':carParking', $carParking);
        $stmt->bindParam(':listedBy', $listedBy);
        $stmt->bindParam(':facing', $facing);
        $stmt->bindParam(':maintenanceMonthly', $maintenanceMonthly);
        $stmt->bindParam(':projectName', $projectName);

        // Execute the statement
        $stmt->execute();

        $response['success'] = true;
        $response['message'] = 'Property added successfully!';

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