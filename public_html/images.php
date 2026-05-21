<?php
$pageTitle = "Images";
require_once 'header.php'; // Includes db_connect.php

$conn = get_db_connection();

// --- 1. Handle Category Filtering ---
$categoryId = null;
$categoryDescription = null; // <-- Initialize description variable
$pageHeader = "All Images";

if (isset($_GET['cat_id']) && filter_var($_GET['cat_id'], FILTER_VALIDATE_INT)) {
    $categoryId = (int)$_GET['cat_id'];
}

// Fetch all categories to populate the filter dropdown
$categoriesResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");

if ($categoryId) {
    // If filtering, fetch the category name AND description
    $stmt = $conn->prepare("SELECT name, description FROM categories WHERE id = ?");
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $catNameResult = $stmt->get_result();
    if ($catNameRow = $catNameResult->fetch_assoc()) {
        $pageHeader = "Images: " . htmlspecialchars($catNameRow['name']);
        $categoryDescription = $catNameRow['description']; // <-- Store the description
    }
}

// --- 2. Build the Main Query for images ---
$sql = "
    SELECT
        i.id AS image_id, i.title, i.filename, i.description,
        q.question, q.correct_answer,
        COUNT(r.id) AS total_runs,
        AVG(CASE WHEN r.score = 'Correct' THEN 1 ELSE 0 END) * 100 AS correct_pct,
        AVG(CASE WHEN r.score = 'Incorrect' THEN 1 ELSE 0 END) * 100 AS incorrect_pct,
        AVG(CASE WHEN r.score = 'Not Found' THEN 1 ELSE 0 END) * 100 AS not_found_pct,
        AVG(CASE WHEN r.score = 'Refusal' THEN 1 ELSE 0 END) * 100 AS refusal_pct
    FROM images i
    JOIN questions q ON i.id = q.image_id
    LEFT JOIN runs r ON q.id = r.question_id
";

if ($categoryId) {
    $sql .= " WHERE i.category_id = ?";
}

$sql .= " GROUP BY i.id ORDER BY i.created_at DESC";

$stmt = $conn->prepare($sql);
if ($categoryId) {
    $stmt->bind_param("i", $categoryId);
}
$stmt->execute();
$imagesResult = $stmt->get_result();
?>

<main id="main-content">
    <h1><?php echo $pageHeader; ?></h1>

    <form action="images.php" method="get" class="filter-form">
        <label for="cat_id">Filter by Category:</label>
        <select name="cat_id" id="cat_id">
            <option value="">All Categories</option>
            <?php mysqli_data_seek($categoriesResult, 0); // Reset pointer for second loop ?>
            <?php while($category = $categoriesResult->fetch_assoc()): ?>
                <option value="<?php echo $category['id']; ?>" <?php if ($categoryId == $category['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit">Go</button>
    </form>

    <?php if ($categoryDescription): ?>
        <blockquote><?php echo htmlspecialchars($categoryDescription); ?></blockquote>
    <?php endif; ?>

    <hr>

    <?php if ($imagesResult->num_rows > 0): ?>
        <?php while($image = $imagesResult->fetch_assoc()): ?>
            <div class="image-listing">
                <h2>
                    <a href="image.php?id=<?php echo $image['image_id']; ?>">
                        <?php echo htmlspecialchars($image['title']); ?>
                    </a>
                </h2>
                <img src="images/<?php echo htmlspecialchars($image['filename']); ?>" alt="<?php echo htmlspecialchars($image['description']); ?>" class="thumbnail">
                <p><strong>Question:</strong> <?php echo htmlspecialchars($image['question']); ?></p>
                <p><strong>Answer:</strong> <?php echo htmlspecialchars($image['correct_answer']); ?></p>

                <?php if ($image['total_runs'] > 0): ?>
                    <p class="stats">
                        <em>Results:
                            <?php echo round($image['correct_pct'], 1); ?>% Correct,
                            <?php echo round($image['incorrect_pct'], 1); ?>% Incorrect,
                            <?php echo round($image['not_found_pct'], 1); ?>% Not Found,
                            <?php echo round($image['refusal_pct'], 1); ?>% Refusal
                        </em>
                    </p>
                <?php else: ?>
                    <p class="stats"><em>No results yet.</em></p>
                <?php endif; ?>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No images found for this category.</p>
    <?php endif; ?>

</main>

<?php
$conn->close();
require_once 'footer.php';
?>