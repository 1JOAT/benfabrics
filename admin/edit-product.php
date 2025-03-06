<?php
session_start();
// require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

checkAuth();

$success_message = '';
$error_message = '';
$product = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $color = trim($_POST['color']);
    $fabric_type = trim($_POST['fabric_type']);
    $stock = intval($_POST['stock']);
    $id = intval($_POST['id']);

    // Handle new image upload if provided
    $image_url = $product['image_url'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/products/";
        $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = '/benfabrics/assets/images/products/' . $new_filename;
        }
    }

    $sql = "UPDATE products SET name=?, description=?, price=?, color=?, fabric_type=?, stock=?, image_url=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdssssi", $name, $description, $price, $color, $fabric_type, $stock, $image_url, $id);

    if ($stmt->execute()) {
        $success_message = "Product updated successfully!";
        // Refresh product data
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
    } else {
        $error_message = "Error updating product: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - BENFABRICS Admin</title>
    <link rel="shortcut icon" href="../assets/images/fav.png" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-purple-600 text-white p-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <a href="dashboard.php" class="text-xl font-bold">BENFABRICS Admin</a>
            <div class="space-x-4">
                <a href="manage-products.php" class="hover:text-gray-200">Manage Products</a>
                <a href="logout.php" class="hover:text-gray-200">Logout</a>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 px-4">
        <?php if (!$product): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                Product not found.
            </div>
        <?php else: ?>
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
                <h2 class="text-2xl font-bold mb-4">Edit Product</h2>
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Image</label>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="Current product image" 
                                 class="mt-2 h-48 w-48 object-cover rounded">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">New Image (optional)</label>
                            <input type="file" name="image" accept="image/*" class="mt-1">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" 
                                   required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Price (per yard)</label>
                            <input type="number" name="price" value="<?php echo $product['price']; ?>" step="0.01" 
                                   required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Color</label>
                            <input type="text" name="color" value="<?php echo htmlspecialchars($product['color']); ?>" 
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
                        <textarea name="description" rows="3" required 
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                        ><?php echo htmlspecialchars($product['description']); ?></textarea>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="manage-products.php" 
                           class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">
                            Cancel
                        </a>
                        <button type="submit" name="update" 
                                class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                            Update Product
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 