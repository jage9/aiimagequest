<?php
require_once 'db_connect.php';

// --- 1. Get and Validate the Image ID ---
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400); // Bad Request
    die("Error: A valid image ID is required.");
}
$imageId = (int)$_GET['id'];

$conn = get_db_connection();

// --- 2. Fetch Main Image, Question, and Category Data ---
$stmt = $conn->prepare(
    "SELECT
        i.title, i.filename, i.description, i.source,
        q.id AS question_id, q.question, q.correct_answer,
        c.id AS category_id, c.name AS category_name
     FROM images i
     JOIN questions q ON i.id = q.image_id
     JOIN categories c ON i.category_id = c.id
     WHERE i.id = ?"
);
$stmt->bind_param("i", $imageId);
$stmt->execute();
$result = $stmt->get_result();
$imageData = $result->fetch_assoc();

if (!$imageData) {
    http_response_code(404); // Not Found
    die("Error: Image with ID {$imageId} not found.");
}
$questionId = $imageData['question_id'];

$pageTitle = $imageData['title'];
require_once 'header.php';

// --- 3. Fetch Overall Summary Data ---
$stmt = $conn->prepare("SELECT score, COUNT(*) as count FROM runs WHERE question_id = ? GROUP BY score");
$stmt->bind_param("i", $questionId);
$stmt->execute();
$summaryResult = $stmt->get_result();

$summaryData = ['Correct' => 0, 'Incorrect' => 0, 'Not Found' => 0, 'Refusal' => 0];
$totalRuns = 0;
while ($row = $summaryResult->fetch_assoc()) {
    if (isset($summaryData[$row['score']])) {
        $summaryData[$row['score']] = $row['count'];
        $totalRuns += $row['count'];
    }
}

// --- 4. Fetch Model-by-Model Breakdown ---
$stmt = $conn->prepare(
    "SELECT m.id AS model_id, m.provider, m.api_identifier, r.score, r.response, r.latency_ms,
            r.prompt_tokens, r.completion_tokens, r.cost
     FROM runs r
     JOIN models m ON r.model_id = m.id
     WHERE r.question_id = ?
     ORDER BY
        CASE r.score
            WHEN 'Correct'   THEN 1
            WHEN 'Not Found' THEN 2
            WHEN 'Incorrect' THEN 3
            WHEN 'Refusal'   THEN 4
            ELSE 5
        END,
        r.cost ASC"
);
$stmt->bind_param("i", $questionId);
$stmt->execute();
$breakdownResult = $stmt->get_result();
?>

<main id="main-content">
    <h1><?php echo htmlspecialchars($imageData['title']); ?></h1>
    
    <figure class="main-figure">
        <img src="images/<?php echo htmlspecialchars($imageData['filename']); ?>" alt="" class="main-image">
        <figcaption>
            <?php echo htmlspecialchars($imageData['description']); ?>
        </figcaption>
    </figure>

    <p><a href="images/<?php echo htmlspecialchars($imageData['filename']); ?>">Download full-size image</a></p>
    
    <div class="image-meta">
        <p><strong>Category:</strong> 
            <a href="images.php?cat_id=<?php echo $imageData['category_id']; ?>">
                <?php echo htmlspecialchars($imageData['category_name']); ?>
            </a>
        </p>
        <p><strong>Source:</strong> <?php echo htmlspecialchars($imageData['source']); ?></p>
    </div>

    <hr>

    <h2>The Quest</h2>
    <p><strong>Question:</strong> <?php echo htmlspecialchars($imageData['question']); ?></p>
    <p><strong>Correct Answer:</strong> <?php echo htmlspecialchars($imageData['correct_answer']); ?></p>

    <hr>

    <h2>Overall Results</h2>
    <?php if ($totalRuns > 0): ?>
        <div class="summary-lines">
            <p><?php echo $summaryData['Correct']; ?> correct (<?php echo round(($summaryData['Correct'] / $totalRuns) * 100, 1); ?>%)</p>
            <p><?php echo $summaryData['Incorrect']; ?> incorrect (<?php echo round(($summaryData['Incorrect'] / $totalRuns) * 100, 1); ?>%)</p>
            <p><?php echo $summaryData['Not Found']; ?> not found (<?php echo round(($summaryData['Not Found'] / $totalRuns) * 100, 1); ?>%)</p>
            <p><?php echo $summaryData['Refusal']; ?> refusal (<?php echo round(($summaryData['Refusal'] / $totalRuns) * 100, 1); ?>%)</p>
        </div>
    <?php else: ?>
        <p>No model results have been logged for this image yet.</p>
    <?php endif; ?>

    <hr>

    <h2>Model-by-Model Breakdown</h2>
    <?php while ($row = $breakdownResult->fetch_assoc()): ?>
        <h3>
            <a href="model.php?id=<?php echo $row['model_id']; ?>">
                <?php echo htmlspecialchars($row['provider'] . ': ' . $row['api_identifier']); ?>
            </a>
        </h3>
        <ul>
            <li>
                <strong>Result:</strong> <?php echo htmlspecialchars($row['score']); ?>
            </li>
            <li>
                <strong>Full Response:</strong>
                <blockquote><?php echo nl2br(htmlspecialchars($row['response'])); ?></blockquote>
            </li>
            <li class="stats">
                <em>Stats: <?php echo ($row['prompt_tokens'] + $row['completion_tokens']); ?> Tokens, $<?php echo number_format($row['cost'], 6); ?> Cost, <?php echo number_format($row['latency_ms'] / 1000, 2); ?> seconds</em>
            </li>
        </ul>
    <?php endwhile; ?>
</main>

<?php
// Clean up database resources
$stmt->close();
$conn->close();

// Include the footer to close the page
require_once 'footer.php';
?>