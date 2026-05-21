<?php
$pageTitle = "About";
require_once 'header.php';
?>

<main id="main-content">
    <h1>About AI Image Quest</h1>

    <p>AI Image Quest benchmarks AI vision models on their ability to read and interpret real-world images — text on signs, product labels, safety information, and similar content that matters for accessibility.</p>

    <p>Each image is paired with a specific question and a verified correct answer. Models are queried via <a href="https://openrouter.ai">OpenRouter</a> and their responses are scored automatically. Results are published on the <a href="index.php">leaderboard</a>.</p>

    <h2>Scoring</h2>
    <table>
        <thead>
            <tr>
                <th scope="col">Score</th>
                <th scope="col">Meaning</th>
            </tr>
        </thead>
        <tbody>
            <tr><th scope="row">Correct</th><td>Answer matches exactly, or fuzzy similarity &ge; 90%</td></tr>
            <tr><th scope="row">Incorrect</th><td>Model gave a definite answer but it was wrong</td></tr>
            <tr><th scope="row">Not Found</th><td>Model indicated the information wasn't visible in the image</td></tr>
            <tr><th scope="row">Refusal</th><td>Model declined to answer (content policy, safety filter, etc.)</td></tr>
        </tbody>
    </table>

    <h2>Project</h2>
    <p>Open source. Code and setup instructions at <a href="https://github.com/jage9/aiimagequest">github.com/jage9/aiimagequest</a>.</p>
</main>

<?php require_once 'footer.php'; ?>
