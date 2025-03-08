<?php
session_start();
// require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

checkAuth();

$success_message = '';
$error_message = '';
$product = null;
$product_images = [];

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    
    // Fetch product details
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    // Fetch all images for this product
    if ($product) {
        $img_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
        $img_stmt = $conn->prepare($img_sql);
        $img_stmt->bind_param("i", $product_id);
        $img_stmt->execute();
        $product_images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update' && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $fabric_type = trim($_POST['fabric_type']);
        $stock = intval($_POST['stock']);

        // Start transaction
        $conn->begin_transaction();

        try {
            // Update product details
            $sql = "UPDATE products SET name = ?, description = ?, price = ?, fabric_type = ?, stock = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdsii", $name, $description, $price, $fabric_type, $stock, $product_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating product: " . $conn->error);
            }

            // Handle existing images updates
            if (isset($_POST['existing_colors'])) {
                foreach ($_POST['existing_colors'] as $image_id => $color) {
                    $sql = "UPDATE product_images SET color = ? WHERE id = ? AND product_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sii", $color, $image_id, $product_id);
                    $stmt->execute();
                }
            }

            // Handle image deletions
            if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    // Get image URL before deletion
                    $sql = "SELECT image_url FROM product_images WHERE id = ? AND product_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $image_id, $product_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($image = $result->fetch_assoc()) {
                        // Delete the physical file
                        $file_path = "../" . $image['image_url'];
                        if (file_exists($file_path)) {
                            unlink($file_path);
                        }
                    }

                    // Delete from database
                    $sql = "DELETE FROM product_images WHERE id = ? AND product_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $image_id, $product_id);
                    $stmt->execute();
                }
            }

            // Handle new image uploads
            if (isset($_FILES['new_images']) && isset($_POST['new_colors'])) {
                $target_dir = "../assets/images/products/";
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                $total_new_images = count($_FILES['new_images']['name']);
                $new_colors = $_POST['new_colors'];

                for ($i = 0; $i < $total_new_images; $i++) {
                    if ($_FILES['new_images']['error'][$i] == 0) {
                        $file_extension = strtolower(pathinfo($_FILES["new_images"]["name"][$i], PATHINFO_EXTENSION));
                        $new_filename = uniqid() . '.' . $file_extension;
                        $target_file = $target_dir . $new_filename;

                        // Verify file type
                        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
                        if (!in_array($file_extension, $allowed_types)) {
                            throw new Exception("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
                        }

                        if (!move_uploaded_file($_FILES["new_images"]["tmp_name"][$i], $target_file)) {
                            throw new Exception("Sorry, there was an error uploading one of your files.");
                        }

                        $image_url = 'assets/images/products/' . $new_filename;
                        $color = trim($new_colors[$i]);
                        $is_primary = (empty($product_images) && $i == 0) ? 1 : 0;

                        $sql = "INSERT INTO product_images (product_id, image_url, color, is_primary) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("issi", $product_id, $image_url, $color, $is_primary);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error adding new image: " . $conn->error);
                        }
                    }
                }
            }

            // Set primary image if specified
            if (isset($_POST['primary_image'])) {
                // First, reset all images to non-primary
                $sql = "UPDATE product_images SET is_primary = 0 WHERE product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();

                // Then set the selected image as primary
                $primary_image_id = intval($_POST['primary_image']);
                $sql = "UPDATE product_images SET is_primary = 1 WHERE id = ? AND product_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $primary_image_id, $product_id);
                $stmt->execute();
            }

            $conn->commit();
            $success_message = "Product updated successfully!";

            // Refresh product data
            $sql = "SELECT * FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();

            $img_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
            $img_stmt = $conn->prepare($img_sql);
            $img_stmt->bind_param("i", $product_id);
            $img_stmt->execute();
            $product_images = $img_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
}

if (!$product) {
    header("Location: manage-products.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - BENFABRICS</title>
    <link rel="shortcut icon" href="../assets/images/fav.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-purple-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <span class="text-white text-lg font-bold">BENFABRICS Admin</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="manage-products.php" class="text-white hover:text-gray-200 text-sm">Back to Products</a>
                    <a href="logout.php" class="text-white hover:text-gray-200 text-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <?php if ($success_message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-6">Edit Product</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Product Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" 
                               required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" 
                               required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fabric Type</label>
                        <input type="text" name="fabric_type" value="<?php echo htmlspecialchars($product['fabric_type']); ?>" 
                               required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Stock</label>
                        <input type="number" name="stock" value="<?php echo $product['stock']; ?>" 
                               required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3" 
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>

                <!-- Existing Images -->
                <?php if (!empty($product_images)): ?>
                    <div class="space-y-4">
                        <h3 class="text-lg font-medium">Current Images</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($product_images as $image): ?>
                                <div class="border rounded-lg p-4 space-y-3">
                                    <img src="../<?php echo htmlspecialchars($image['image_url']); ?>" 
                                         alt="Product Image" 
                                         class="w-full h-48 object-cover rounded">
                                    
                                    <div class="flex items-center space-x-2">
                                        <input type="text" 
                                               name="existing_colors[<?php echo $image['id']; ?>]" 
                                               value="<?php echo htmlspecialchars($image['color']); ?>" 
                                               class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                               placeholder="Color">
                                        
                                        <label class="inline-flex items-center">
                                            <input type="radio" 
                                                   name="primary_image" 
                                                   value="<?php echo $image['id']; ?>"
                                                   <?php echo $image['is_primary'] ? 'checked' : ''; ?>
                                                   class="text-purple-600 focus:ring-purple-500">
                                            <span class="ml-2 text-sm text-gray-600">Primary</span>
                                        </label>
                                        
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" 
                                                   name="delete_images[]" 
                                                   value="<?php echo $image['id']; ?>"
                                                   class="text-red-600 focus:ring-red-500">
                                            <span class="ml-2 text-sm text-red-600">Delete</span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- New Images -->
                <div id="new-images" class="space-y-4">
                    <h3 class="text-lg font-medium">Add New Images</h3>
                    <div class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700">Image</label>
                                <input type="file" name="new_images[]" accept="image/*" class="mt-1 block w-full">
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700">Color</label>
                                <input type="text" name="new_colors[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" onclick="addNewImageField()" 
                            class="text-purple-600 hover:text-purple-700">
                        + Add Another Image
                    </button>
                </div>

                <div class="flex justify-end space-x-3">
                    <a href="manage-products.php" 
                       class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                        Update Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function addNewImageField() {
        const container = document.querySelector('#new-images .space-y-4');
        const newField = document.createElement('div');
        newField.className = 'flex items-center space-x-4';
        newField.innerHTML = `
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Image</label>
                <input type="file" name="new_images[]" accept="image/*" class="mt-1 block w-full">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Color</label>
                <input type="text" name="new_colors[]" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-700">
                Remove
            </button>
        `;
        container.appendChild(newField);
    }
    </script>
</body>
</html> 