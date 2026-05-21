<?php
require_once 'header.php';

$conn = get_db_connection();

// --- Main Leaderboard Query ---
$sql = "
    SELECT
        m.id AS model_id,
        m.provider,
        m.api_identifier,
        COUNT(r.id) AS total_runs,
        AVG(CASE WHEN r.score = 'Correct' THEN 1 ELSE 0 END) * 100 AS correct_pct,
        AVG(CASE WHEN r.score = 'Incorrect' THEN 1 ELSE 0 END) * 100 AS incorrect_pct,
        AVG(CASE WHEN r.score = 'Not Found' THEN 1 ELSE 0 END) * 100 AS not_found_pct,
        AVG(CASE WHEN r.score = 'Refusal' THEN 1 ELSE 0 END) * 100 AS refusal_pct
    FROM models m
    LEFT JOIN runs r ON m.id = r.model_id
    GROUP BY m.id
    ORDER BY
        correct_pct DESC,
        not_found_pct DESC,
        incorrect_pct ASC,
        refusal_pct ASC,
        provider ASC,
        api_identifier ASC
";
$mainLeaderboardResult = $conn->query($sql);

// --- Fetch Categories for Breakdown ---
$categoriesResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
?>

<main id="main-content">
    <h1>AI Image Quest</h1>
    <h2>Overall Leaderboard</h2>
    
    <?php if ($mainLeaderboardResult && $mainLeaderboardResult->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th scope="col">Model</th>
                <th scope="col">Correct</th>
                <th scope="col">Incorrect</th>
                <th scope="col">Not Found</th>
                <th scope="col">Refusal</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $mainLeaderboardResult->fetch_assoc()): ?>
            <tr>
                <th scope="row"><a href="model.php?id=<?php echo (int) $row['model_id']; ?>"><?php echo htmlspecialchars($row['provider'] . ': ' . $row['api_identifier']); ?></a></th>
                <td><?php echo round($row['correct_pct'], 1); ?>%</td>
                <td><?php echo round($row['incorrect_pct'], 1); ?>%</td>
                <td><?php echo round($row['not_found_pct'], 1); ?>%</td>
                <td><?php echo round($row['refusal_pct'], 1); ?>%</td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p>No benchmark results have been logged yet. Run the benchmark script to populate this leaderboard.</p>
    <?php endif; ?>

    <hr>

    <?php if ($categoriesResult && $categoriesResult->num_rows > 0): ?>
        <?php while($category = $categoriesResult->fetch_assoc()): ?>
            <h2><?php echo htmlspecialchars($category['name']); ?></h2>
            <?php
            // The category breakdown query is fine as is, but we'll wrap its output in a check too.
            $stmt = $conn->prepare("
                SELECT
                    m.id AS model_id, m.provider, m.api_identifier,
                    AVG(CASE WHEN r.score = 'Correct' THEN 1 ELSE 0 END) * 100 AS correct_pct
                FROM models m
                LEFT JOIN runs r ON m.id = r.model_id
                JOIN questions q ON r.question_id = q.id
                JOIN images i ON q.image_id = i.id
                WHERE i.category_id = ?
                GROUP BY m.id
                ORDER BY correct_pct DESC, m.provider ASC, m.api_identifier ASC
            ");
            $stmt->bind_param("i", $category['id']);
            $stmt->execute();
            $categoryLeaderboardResult = $stmt->get_result();
            ?>
            <?php if ($categoryLeaderboardResult && $categoryLeaderboardResult->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th scope="col">Model</th>
                        <th scope="col">% Correct</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $categoryLeaderboardResult->fetch_assoc()): ?>
                    <tr>
                        <th scope="row"><a href="model.php?id=<?php echo (int) $row['model_id']; ?>"><?php echo htmlspecialchars($row['provider'] . ': ' . $row['api_identifier']); ?></a></th>
                        <td><?php echo round($row['correct_pct'], 1); ?>%</td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No results found for this category yet.</p>
            <?php endif; ?>
        <?php endwhile; ?>
    <?php endif; ?>

</main>

<?php
$conn->close();
require_once 'footer.php';
?>