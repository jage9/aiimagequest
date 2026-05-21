<?php
require_once 'db_connect.php';

if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    http_response_code(400);
    die("Error: A valid model ID is required.");
}
$modelId = (int)$_GET['id'];

$conn = get_db_connection();

$stmt = $conn->prepare("SELECT provider, api_identifier FROM models WHERE id = ?");
$stmt->bind_param("i", $modelId);
$stmt->execute();
$modelData = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$modelData) {
    http_response_code(404);
    die("Error: Model not found.");
}

$pageTitle = $modelData['provider'] . ': ' . $modelData['api_identifier'];
require_once 'header.php';

// Overall stats
$stmt = $conn->prepare(
    "SELECT score, COUNT(*) AS count FROM runs WHERE model_id = ? GROUP BY score"
);
$stmt->bind_param("i", $modelId);
$stmt->execute();
$statsResult = $stmt->get_result();
$stmt->close();

$stats = ['Correct' => 0, 'Incorrect' => 0, 'Not Found' => 0, 'Refusal' => 0];
$total = 0;
while ($row = $statsResult->fetch_assoc()) {
    if (isset($stats[$row['score']])) {
        $stats[$row['score']] = (int)$row['count'];
        $total += (int)$row['count'];
    }
}

// Per-image breakdown
$stmt = $conn->prepare(
    "SELECT
        i.id AS image_id, i.title, i.filename,
        q.question, q.correct_answer,
        r.score, r.response, r.latency_ms, r.prompt_tokens, r.completion_tokens, r.cost
     FROM runs r
     JOIN questions q ON r.question_id = q.id
     JOIN images i ON q.image_id = i.id
     WHERE r.model_id = ?
     ORDER BY
        CASE r.score
            WHEN 'Correct'   THEN 1
            WHEN 'Not Found' THEN 2
            WHEN 'Incorrect' THEN 3
            WHEN 'Refusal'   THEN 4
            ELSE 5
        END,
        i.title ASC"
);
$stmt->bind_param("i", $modelId);
$stmt->execute();
$runsResult = $stmt->get_result();
?>

<main id="main-content">
    <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

    <h2>Overall Results</h2>
    <?php if ($total > 0): ?>
        <div class="summary-lines">
            <p><?php echo $stats['Correct']; ?> correct (<?php echo round($stats['Correct'] / $total * 100, 1); ?>%)</p>
            <p><?php echo $stats['Incorrect']; ?> incorrect (<?php echo round($stats['Incorrect'] / $total * 100, 1); ?>%)</p>
            <p><?php echo $stats['Not Found']; ?> not found (<?php echo round($stats['Not Found'] / $total * 100, 1); ?>%)</p>
            <p><?php echo $stats['Refusal']; ?> refusal (<?php echo round($stats['Refusal'] / $total * 100, 1); ?>%)</p>
        </div>
    <?php else: ?>
        <p>No benchmark results for this model yet.</p>
    <?php endif; ?>

    <hr>

    <h2>Results by Image</h2>
    <?php while ($run = $runsResult->fetch_assoc()): ?>
        <h3>
            <a href="image.php?id=<?php echo $run['image_id']; ?>">
                <?php echo htmlspecialchars($run['title']); ?>
            </a>
        </h3>
        <ul>
            <li><strong>Question:</strong> <?php echo htmlspecialchars($run['question']); ?></li>
            <li><strong>Correct Answer:</strong> <?php echo htmlspecialchars($run['correct_answer']); ?></li>
            <li><strong>Result:</strong> <?php echo htmlspecialchars($run['score']); ?></li>
            <li><strong>Response:</strong>
                <blockquote><?php echo nl2br(htmlspecialchars($run['response'])); ?></blockquote>
            </li>
            <li class="stats"><em>Stats: <?php echo ($run['prompt_tokens'] + $run['completion_tokens']); ?> tokens, $<?php echo number_format($run['cost'], 6); ?> cost, <?php echo number_format($run['latency_ms'] / 1000, 2); ?> seconds</em></li>
        </ul>
    <?php endwhile; ?>
</main>

<?php
$stmt->close();
$conn->close();
require_once 'footer.php';
?>
