<?php
require_once 'config.php';

if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $conn = get_db_connection();

    // VULNERABLE: Direct SQL concatenation
    $sql = "SELECT * FROM products WHERE name LIKE '%$search%' OR description LIKE '%$search%'";

    log_to_syslog("SEARCH_QUERY | IP=" . $_SERVER['REMOTE_ADDR'] . " | Query=$sql", LOG_INFO);

    $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Search</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        input[type="text"] { width: 70%; padding: 10px; }
        input[type="submit"] { padding: 10px 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #667eea; color: white; }
    </style>
</head>
<body>
    <h1>Product Search</h1>
    <form method="GET">
        <input type="text" name="search" placeholder="Search products..."
               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        <input type="submit" value="Search">
    </form>

    <?php if (isset($result) && $result->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Description</th><th>Price</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['name']; ?></td>
                    <td><?php echo $row['description']; ?></td>
                    <td>$<?php echo $row['price']; ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php elseif (isset($_GET['search'])): ?>
        <p>No products found.</p>
    <?php endif; ?>

    <p style="margin-top: 30px;"><a href="index.php">Back to Home</a></p>
</body>
</html>
