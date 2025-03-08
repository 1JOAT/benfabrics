<?php
session_start();
// require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

checkAuth();

$success_message = '';
$error_message = '';

// Handle product upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            // Create directory if it doesn't exist
            $target_dir = "../assets/images/products/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $fabric_type = trim($_POST['fabric_type']);
            $stock = intval($_POST['stock']);

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert product first
                $sql = "INSERT INTO products (name, description, price, fabric_type, stock) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdsi", $name, $description, $price, $fabric_type, $stock);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error adding product: " . $conn->error);
                }

                $product_id = $conn->insert_id;

                // Handle multiple image uploads
                $total_images = count($_FILES['images']['name']);
                $colors = $_POST['colors'];

                for ($i = 0; $i < $total_images; $i++) {
                    if ($_FILES['images']['error'][$i] == 0) {
                        $file_extension = strtolower(pathinfo($_FILES["images"]["name"][$i], PATHINFO_EXTENSION));
                        $new_filename = uniqid() . '.' . $file_extension;
                        $target_file = $target_dir . $new_filename;

                        // Verify file type
                        $allowed_types = array('jpg', 'jpeg', 'png', 'gif');
                        if (!in_array($file_extension, $allowed_types)) {
                            throw new Exception("Sorry, only JPG, JPEG, PNG & GIF files are allowed.");
                        }

                        if (!move_uploaded_file($_FILES["images"]["tmp_name"][$i], $target_file)) {
                            throw new Exception("Sorry, there was an error uploading one of your files.");
                        }

                        // Store relative path without leading slash
                        $image_url = 'assets/images/products/' . $new_filename;
                        $color = trim($colors[$i]);
                        $is_primary = ($i == 0) ? 1 : 0; // First image is primary

                        // Insert image record
                        $sql = "INSERT INTO product_images (product_id, image_url, color, is_primary) VALUES (?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("issi", $product_id, $image_url, $color, $is_primary);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error adding image: " . $conn->error);
                        }
                    }
                }

                // Commit transaction
                $conn->commit();
                $success_message = "Product added successfully with all images!";
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error_message = $e->getMessage();
            }
        } elseif ($_POST['action'] == 'delete' && isset($_POST['product_id'])) {
            $product_id = intval($_POST['product_id']);
            
            // The images will be automatically deleted due to ON DELETE CASCADE
            $sql = "DELETE FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $product_id);
            
            if ($stmt->execute()) {
                $success_message = "Product deleted successfully!";
            } else {
                $error_message = "Error deleting product: " . $conn->error;
            }
        }
    }
}

// Fetch all products with their primary images
$sql = "SELECT p.*, pi.image_url, pi.color 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - BENFABRICS</title>
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
                    <span class="text-white">Welcome Admin</span>
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

        <!-- Add Product Form -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-bold mb-4">Add New Product</h2>
            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Product Name</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input type="number" name="price" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Fabric Type</label>
                        <input type="text" name="fabric_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Stock</label>
                        <input type="number" name="stock" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"></textarea>
                </div>

                <div id="image-uploads" class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Product Image</label>
                            <input type="file" name="images[]" accept="image/*" required class="mt-1 block w-full">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Color</label>
                            <input type="text" name="colors[]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addImageUpload()" class="text-purple-600 hover:text-purple-700">
                    + Add Another Image
                </button>

                <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                    Add Product
                </button>
            </form>
        </div>

        <!-- Products List -->
        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-2xl font-bold mb-4">Manage Products</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($row['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($row['image_url']); ?>" 
                                             alt="Product" 
                                             class="h-20 w-20 object-cover rounded">
                                        <?php if (isset($_GET['debug'])): ?>
                                            <div class="text-xs text-gray-500 mt-1">
                                                Path: <?php echo htmlspecialchars($row['image_url']); ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="h-20 w-20 bg-gray-100 flex items-center justify-center rounded">
                                            <span class="text-gray-400">No image</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($row['name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    ₦<?php echo number_format($row['price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php echo htmlspecialchars($row['stock']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                    <a href="edit-product.php?id=<?php echo $row['id']; ?>" class="text-purple-600 hover:text-purple-900 ml-4">Edit</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function addImageUpload() {
        const container = document.getElementById('image-uploads');
        const newUpload = document.createElement('div');
        newUpload.className = 'flex items-center space-x-4';
        newUpload.innerHTML = `
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Product Image</label>
                <input type="file" name="images[]" accept="image/*" required class="mt-1 block w-full">
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700">Color</label>
                <input type="text" name="colors[]" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-red-600 hover:text-red-700">
                Remove
            </button>
        `;
        container.appendChild(newUpload);
    }
    </script>
</body>
</html>