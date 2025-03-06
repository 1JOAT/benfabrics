<?php
session_start();
// require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';

checkAuth();

// Get recent activity
$recent_activity_sql = "
    SELECT 
        p.id,
        p.name,
        p.created_at,
        'product_added' as activity_type
    FROM products p
    ORDER BY p.created_at DESC
    LIMIT 10
";
$recent_activity = $conn->query($recent_activity_sql);

// Get product statistics
$stats_sql = "SELECT 
    COUNT(*) as total_products,
    COUNT(DISTINCT color) as unique_colors,
    COUNT(DISTINCT fabric_type) as fabric_types
FROM products";
$stats = $conn->query($stats_sql)->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BENFABRICS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
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

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h4 class="text-lg font-semibold">Total Products</h4>
                                <p class="text-2xl font-bold text-purple-600"><?php echo $stats['total_products']; ?></p>
                            </div>
                        </div>
                        <a href="manage-products.php" 
                           class="inline-flex items-center px-3 py-2 border border-purple-600 text-sm font-medium rounded-md text-purple-600 hover:bg-purple-50">
                            View All
                        </a>
                    </div>
                </div>
                <!-- Add more stat cards for unique colors and fabric types -->
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900">Quick Actions</h3>
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <a href="manage-products.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                            Add New Product
                        </a>
                        <!-- Add more quick actions -->
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                    <div class="space-y-4">
                        <?php if ($recent_activity->num_rows > 0): ?>
                            <?php while($activity = $recent_activity->fetch_assoc()): ?>
                                <div class="flex items-center justify-between border-b pb-4">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            New product added: <?php echo htmlspecialchars($activity['name']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('F j, Y g:i A', strtotime($activity['created_at'])); ?>
                                        </p>
                                    </div>
                                    <a href="edit-product.php?id=<?php echo $activity['id']; ?>" 
                                       class="text-purple-600 hover:text-purple-900">
                                        View Details
                                    </a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 