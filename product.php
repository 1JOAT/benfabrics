<?php
// require_once 'includes/config.php';
require_once 'includes/db.php';

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$color = isset($_GET['color']) ? trim($_GET['color']) : '';
$fabric_type = isset($_GET['fabric_type']) ? trim($_GET['fabric_type']) : '';

// At the top of the file, update the query building
$where_conditions = [];
$params = [];
$types = "";

if (isset($_GET['min_price']) && $_GET['min_price'] !== '') {
    $where_conditions[] = "price >= ?";
    $params[] = floatval($_GET['min_price']);
    $types .= "d";
}

if (isset($_GET['max_price']) && $_GET['max_price'] !== '') {
    $where_conditions[] = "price <= ?";
    $params[] = floatval($_GET['max_price']);
    $types .= "d";
}

if (isset($_GET['color']) && $_GET['color'] !== '') {
    $where_conditions[] = "color = ?";
    $params[] = $_GET['color'];
    $types .= "s";
}

if (isset($_GET['fabric_type']) && $_GET['fabric_type'] !== '') {
    $where_conditions[] = "fabric_type = ?";
    $params[] = $_GET['fabric_type'];
    $types .= "s";
}

$sql = "SELECT * FROM products";
if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get unique colors and fabric types for filters
$colors = $conn->query("SELECT DISTINCT color FROM products ORDER BY color")->fetch_all(MYSQLI_ASSOC);
$fabric_types = $conn->query("SELECT DISTINCT fabric_type FROM products ORDER BY fabric_type")->fetch_all(MYSQLI_ASSOC);

// After your SQL query execution, add a variable to check if there are results
$has_results = $result->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - BENFABRICS</title>
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
                <?php while($row = $result->fetch_assoc()): ?>
                    <div class="bg-white shadow rounded-lg overflow-hidden">
                        <!-- Clickable image -->
                        <div class="relative cursor-pointer aspect-w-1 aspect-h-1" 
                             onclick="openImageModal('<?php echo htmlspecialchars($row['image_url']); ?>')">
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($row['name'] ?? 'Fabric'); ?>"
                                 class="w-full h-48 sm:h-56 md:h-64 object-cover transition duration-300 hover:opacity-90"
                                 onerror="this.src='/benfabrics/assets/images/placeholder.jpg'">
                            <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 hover:bg-opacity-30 transition-all duration-300">
                                <span class="text-white opacity-0 hover:opacity-100">Click to view</span>
                            </div>
                        </div>
                        <!-- Product details -->
                        <div class="p-4">
                            <?php if (!empty($row['name'])): ?>
                                <h3 class="text-lg font-semibold mb-2"><?php echo htmlspecialchars($row['name']); ?></h3>
                            <?php endif; ?>
                            <div class="flex flex-wrap justify-between items-center mb-2">
                                <div class="text-purple-600">
                                    <span class="font-bold">₦<?php echo number_format($row['price'], 2); ?></span>
                                    <span class="text-sm text-gray-600">per yard</span>
                                </div>
                                <span class="text-gray-500"><?php echo htmlspecialchars($row['color']); ?></span>
                            </div>
                            <?php if (!empty($row['description'])): ?>
                                <p class="text-gray-600 mb-4 text-sm"><?php echo htmlspecialchars($row['description']); ?></p>
                            <?php endif; ?>
                            <a href="https://wa.me/your_number?text=<?php 
                                $message = 'Hello! I\'m interested in this fabric. ';
                                $message .= 'Color: ' . $row['color'] . '. ';
                                $message .= 'Price: ₦' . number_format($row['price'], 2) . ' per yard. ';
                                $message .= 'How can I proceed with the purchase?';
                                echo urlencode($message); 
                            ?>" 
                               target="_blank"
                               class="block w-full text-center bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700 transition duration-300">
                                Contact on WhatsApp
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="bg-white shadow rounded-lg p-8 text-center">
                <div class="mb-4">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Products Found</h3>
                <p class="text-gray-500 mb-6">
                    <?php
                    $message = "We couldn't find any products";
                    if (isset($_GET['min_price']) || isset($_GET['max_price'])) {
                        $message .= " in your selected price range";
                    }
                    if (isset($_GET['color']) && $_GET['color'] !== '') {
                        $message .= " with the selected color";
                    }
                    if (isset($_GET['fabric_type']) && $_GET['fabric_type'] !== '') {
                        $message .= " of this fabric type";
                    }
                    echo $message . ".";
                    ?>
                </p>
                <a href="product.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                    View All Products
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 hidden z-50" onclick="closeImageModal()">
        <button class="absolute top-4 right-4 text-white text-xl p-2" onclick="closeImageModal()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
        <div class="flex items-center justify-center h-full p-4">
            <img id="modalImage" src="" alt="Enlarged view" 
                 class="max-h-[90vh] max-w-[90vw] w-auto h-auto object-contain">
        </div>
    </div>

    <script>
    function openImageModal(imageSrc) {
        const modal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        modalImage.src = imageSrc;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeImageModal() {
        const modal = document.getElementById('imageModal');
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageModal();
        }
    });

    // Prevent modal from closing when clicking the image
    document.getElementById('modalImage').addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Search form toggle function
    function toggleSearch() {
        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            searchForm.classList.toggle('hidden');
        }
    }
    </script>
</body>
</html> 