<?php
// require_once 'includes/config.php';
require_once 'includes/db.php';

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$color = isset($_GET['color']) ? trim($_GET['color']) : '';
$fabric_type = isset($_GET['fabric_type']) ? trim($_GET['fabric_type']) : '';

// Build the query with JOIN to product_images
$where_conditions = [];
$params = [];
$types = "";

// Base query with JOIN
$sql = "SELECT DISTINCT p.*, pi.image_url, pi.color, pi.is_primary 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id";

// Start with WHERE clause
$where_conditions[] = "(pi.is_primary = 1 OR pi.is_primary IS NULL)";

if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
    $where_conditions[] = "p.price >= ?";
    $params[] = floatval($_GET['min_price']);
    $types .= "d";
}

if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
    $where_conditions[] = "p.price <= ?";
    $params[] = floatval($_GET['max_price']);
    $types .= "d";
}

if (isset($_GET['color']) && $_GET['color'] !== '') {
    // Join with product_images again for color filtering
    $sql = "SELECT DISTINCT p.*, pi.image_url, pi.color, pi.is_primary 
            FROM products p 
            LEFT JOIN product_images pi ON p.id = pi.product_id
            WHERE p.id IN (
                SELECT product_id 
                FROM product_images 
                WHERE color = ?
            )";
    $where_conditions = ["(pi.is_primary = 1 OR pi.is_primary IS NULL)"]; // Reset where conditions
    $params[] = $_GET['color'];
    $types .= "s";
}

if (isset($_GET['fabric_type']) && $_GET['fabric_type'] !== '') {
    $where_conditions[] = "p.fabric_type = ?";
    $params[] = $_GET['fabric_type'];
    $types .= "s";
}

// Add WHERE clause if there are conditions
if (!empty($where_conditions)) {
    if (strpos($sql, 'WHERE') === false) {
        $sql .= " WHERE ";
    } else {
        $sql .= " AND ";
    }
    $sql .= implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get unique colors from product_images table
$colors = $conn->query("SELECT DISTINCT color FROM product_images WHERE color != '' ORDER BY color")->fetch_all(MYSQLI_ASSOC);
// Get unique fabric types from products table
$fabric_types = $conn->query("SELECT DISTINCT fabric_type FROM products WHERE fabric_type != '' ORDER BY fabric_type")->fetch_all(MYSQLI_ASSOC);

// After your SQL query execution, add a variable to check if there are results
$has_results = $result->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Essential Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="Browse our premium selection of Aso Ebi fabrics at BENFABRICS. Find quality fabrics with competitive pricing and fast delivery in Nigeria." />
    <meta name="keywords" content="Aso Ebi fabrics, premium fabrics, Nigerian fabrics, BENFABRICS, fabric collection, quality fabrics" />
    <link rel="canonical" href="https://www.benfabrics.landcraft.site/product.php" />

    <!-- Open Graph / Facebook -->
    <meta property="og:title" content="Products - BENFABRICS" />
    <meta property="og:description" content="Browse our premium selection of Aso Ebi fabrics at BENFABRICS. Find quality fabrics with competitive pricing and fast delivery in Nigeria." />
    <meta property="og:image" content="https://www.benfabrics.landcraft.site/assets/images/hero1.avif" />
    <meta property="og:url" content="https://www.benfabrics.landcraft.site/product.php" />
    <meta property="og:type" content="website" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="Products - BENFABRICS" />
    <meta name="twitter:description" content="Browse our premium selection of Aso Ebi fabrics at BENFABRICS. Find quality fabrics with competitive pricing and fast delivery in Nigeria." />
    <meta name="twitter:image" content="https://www.benfabrics.landcraft.site/assets/images/hero1.avif" />

    <title>Products - BENFABRICS</title>
    <link rel="shortcut icon" href="./assets/images/fav.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; }
        #mobile-menu {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .nav-fixed {
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 50;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg fixed w-full top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <a href="/" class="text-2xl font-bold text-purple-600">BENFABRICS</a>
                
                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-purple-600 focus:outline-none">
                        <span class="sr-only">Open main menu</span>
                        <svg class="block h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex md:items-center md:space-x-4">
                    <a href="index.html" class="text-gray-800 hover:text-purple-600 px-3 py-2">Home</a>

                </div>
            </div>

            <!-- Mobile Navigation Menu -->
            <div id="mobile-menu" class="hidden bg-white w-full">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <a href="index.html" class="block text-gray-800 hover:text-purple-600 px-3 py-2 rounded-md">Home</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Add spacing after fixed nav -->
    <div class="h-16"></div>

    <!-- Products Header -->
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-extrabold tracking-tight text-purple-600 sm:text-4xl">Our Fabric Collection</h2>
            <p class="mt-4 max-w-xl mx-auto text-base text-gray-500">
                Browse our premium selection of asoebi fabrics for every occasion.
            </p>
        </div>
    </div>

    <!-- Search/Filter Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <button onclick="toggleSearch()" 
                class="mb-6 flex items-center text-purple-600 hover:text-purple-700 w-full md:w-auto justify-center md:justify-start">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
            Filter Products
        </button>

        <!-- Search Form -->
        <div id="searchForm" class="hidden mb-8">
            <div class="bg-white shadow rounded-lg p-4 md:p-6">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Price Range</label>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="number" name="min_price" placeholder="Min" 
                                       value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>" 
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                <input type="number" name="max_price" placeholder="Max" 
                                       value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>"
                                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Color</label>
                            <select name="color" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                <option value="">All Colors</option>
                                <?php foreach ($colors as $c): ?>
                                    <option value="<?php echo htmlspecialchars($c['color']); ?>" 
                                            <?php echo isset($_GET['color']) && $_GET['color'] === $c['color'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['color']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-gray-700">Fabric Type</label>
                            <select name="fabric_type" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 text-sm">
                                <option value="">All Types</option>
                                <?php foreach ($fabric_types as $ft): ?>
                                    <option value="<?php echo htmlspecialchars($ft['fabric_type']); ?>"
                                            <?php echo isset($_GET['fabric_type']) && $_GET['fabric_type'] === $ft['fabric_type'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($ft['fabric_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="w-full sm:w-auto bg-purple-600 text-white px-6 py-2 rounded-md hover:bg-purple-700 transition duration-300">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Grid -->
        <?php if ($has_results): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                <?php while($row = $result->fetch_assoc()): 
                    // Get all images for this product
                    $images_sql = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC";
                    $images_stmt = $conn->prepare($images_sql);
                    $images_stmt->bind_param("i", $row['id']);
                    $images_stmt->execute();
                    $product_images = $images_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                ?>
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <!-- Main Image -->
                        <div class="relative">
                            <div class="aspect-w-1 aspect-h-1">
                                <?php if (!empty($product_images)): ?>
                                    <img src="<?php echo htmlspecialchars($product_images[0]['image_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($row['name']); ?>"
                                         class="w-full h-64 object-cover transition duration-300"
                                         id="main-image-<?php echo $row['id']; ?>">
                                <?php else: ?>
                                    <img src="assets/images/hero.png" 
                                         alt="Default Image"
                                         class="w-full h-64 object-cover">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Color Thumbnails -->
                            <?php if (count($product_images) > 1): ?>
                                <div class="absolute bottom-2 left-2 right-2 flex justify-center gap-2 p-2 bg-black bg-opacity-30 rounded-lg">
                                    <?php foreach ($product_images as $image): ?>
                                        <button 
                                            onclick="updateMainImage('<?php echo $row['id']; ?>', '<?php echo htmlspecialchars($image['image_url']); ?>')"
                                            class="w-8 h-8 rounded-full border-2 border-white overflow-hidden hover:border-purple-500 transition-all duration-200"
                                            title="<?php echo htmlspecialchars($image['color']); ?>">
                                            <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['color']); ?>"
                                                 class="w-full h-full object-cover">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Product Info -->
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </h3>
                            
                            <div class="mt-2 flex justify-between items-center">
                                <div class="text-purple-600">
                                    <span class="text-lg font-bold">₦<?php echo number_format($row['price'], 2); ?></span>
                                    <span class="text-sm text-gray-500">per 5 yards</span>
                                </div>
                            </div>

                            <?php if (!empty($row['description'])): ?>
                                <p class="mt-2 text-sm text-gray-500">
                                    <?php echo htmlspecialchars($row['description']); ?>
                                </p>
                            <?php endif; ?>

                            <!-- Available Colors -->
                            <?php if (count($product_images) > 0): ?>
                                <div class="mt-3">
                                    <h4 class="text-sm font-medium text-gray-900">Available Colors:</h4>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <?php foreach ($product_images as $image): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                <?php echo htmlspecialchars($image['color']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="mt-4">
                                <a href="https://wa.me/+2348096983079?text=<?php 
                                    $message = 'Hello! I\'m interested in this fabric: ';
                                    $message .= htmlspecialchars($row['name']) . '. ';
                                    if (!empty($product_images)) {
                                        $message .= 'Available in: ' . implode(', ', array_column($product_images, 'color')) . '. ';
                                    }
                                    $message .= 'Price: ₦' . number_format($row['price'], 2) . ' per 5 yards. ';
                                    $message .= 'How can I proceed with the purchase?';
                                    echo urlencode($message); 
                                ?>" 
                                   target="_blank"
                                   class="block w-full text-center bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition duration-300">
                                    Contact on WhatsApp
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-12">
                <p class="text-gray-500">No products found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
        <div class="relative max-w-4xl w-full">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            <img id="modalImage" src="" alt="Large product image" class="max-h-[80vh] mx-auto">
        </div>
    </div>

    <script>
        // Toggle search form
        function toggleSearch() {
            const searchForm = document.getElementById('searchForm');
            searchForm.classList.toggle('hidden');
        }

        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        });

        // Update main product image
        function updateMainImage(productId, imageUrl) {
            const mainImage = document.getElementById(`main-image-${productId}`);
            mainImage.src = imageUrl;
        }

        // Image modal functions
        function openImageModal(imageUrl) {
            document.getElementById('modalImage').src = imageUrl;
            document.getElementById('imageModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            document.getElementById('imageModal').classList.add('hidden');
            document.body.style.overflow = '';
        }

        // Close modal on background click
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
    </script>
</body>
</html> 