<?php
$pageTitle = "Add New Image";
require_once 'auth.php';
require_once '../header.php';

$conn = get_db_connection();
$categoriesResult = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
?>

<main id="main-content">
    <h1>Add New Image</h1>
    <div id="status-message" role="status"></div>

    <form id="upload-form" enctype="multipart/form-data">
        <fieldset id="upload-fieldset">
            <p>
                <label for="image-file">Select Image:</label><br>
                <input type="file" id="image-file" name="imageFile" required accept="image/jpeg, image/png, image/webp">
            </p>
            <button type="submit">Upload Image</button>
        </fieldset>
    </form>

    <hr>

    <div id="data-entry-section" style="display: none;">
        <h2>Verify and Add Details</h2>

        <figure class="main-figure">
            <img id="preview-image" src="" alt="Image preview will appear here." class="thumbnail">
        </figure>

        <div class="button-group">
            <button id="generate-desc-btn">Generate Description</button>
            <button id="discard-btn" class="discard">Discard Image</button>
        </div>

        <form id="details-form">
            <input type="hidden" id="temp-filename" name="tempFilename">

            <fieldset id="details-fieldset" disabled>
                <p>
                    <label for="description-text">Description:</label><br>
                    <textarea id="description-text" name="description" rows="5" cols="60"></textarea>
                </p>
                <p>
                    <label for="title">Title:</label><br>
                    <input type="text" id="title" name="title" required size="50">
                </p>
                <p>
                    <label for="source">Source (URL):</label><br>
                    <input type="text" id="source" name="source" required size="50">
                </p>
                <p>
                    <label for="category_id">Category:</label><br>
                    <select id="category_id" name="categoryId" required>
                        <option value="">--Select a Category--</option>
                        <?php while ($cat = $categoriesResult->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </p>
                <hr>
                <h3>Benchmark Quest</h3>
                <p>
                    <label for="question">Question:</label><br>
                    <textarea id="question" name="question" required rows="2" cols="50"></textarea>
                </p>
                <p>
                    <label for="correct_answer">Correct Answer:</label><br>
                    <input type="text" id="correct_answer" name="correctAnswer" required size="50">
                </p>
                <button type="submit">Save All</button>
            </fieldset>
        </form>
    </div>

    <p><a href="logout.php">Logout</a></p>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = "<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>";

    const uploadForm       = document.getElementById('upload-form');
    const fileInput        = document.getElementById('image-file');
    const detailsForm      = document.getElementById('details-form');
    const dataEntrySection = document.getElementById('data-entry-section');
    const uploadFieldset   = document.getElementById('upload-fieldset');
    const detailsFieldset  = document.getElementById('details-fieldset');
    const generateDescBtn  = document.getElementById('generate-desc-btn');
    const discardBtn       = document.getElementById('discard-btn');
    const previewImage     = document.getElementById('preview-image');
    const descriptionText  = document.getElementById('description-text');
    const tempFilenameInput= document.getElementById('temp-filename');
    const statusMessage    = document.getElementById('status-message');

    uploadForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        if (fileInput.files.length === 0) {
            statusMessage.textContent = 'Please select an image file first.';
            statusMessage.style.color = 'red';
            return;
        }
        uploadFieldset.disabled = true;
        statusMessage.textContent = 'Uploading...';
        statusMessage.style.color = 'blue';
        const formData = new FormData();
        formData.append('imageFile', fileInput.files[0]);
        formData.append('csrf_token', csrfToken);
        try {
            const response = await fetch('ajax_temp_upload.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                tempFilenameInput.value = result.filename;
                previewImage.src = `serve_temp_image.php?file=${result.filename}`;
                previewImage.alt = 'Preview of uploaded image.';
                dataEntrySection.style.display = 'block';
                statusMessage.textContent = 'Upload successful. Add details or generate a description.';
                statusMessage.style.color = 'green';
                detailsFieldset.disabled = false;
            } else { throw new Error(result.error); }
        } catch (error) {
            statusMessage.textContent = `Upload failed: ${error.message}`;
            statusMessage.style.color = 'red';
            uploadFieldset.disabled = false;
        }
    });

    generateDescBtn.addEventListener('click', async function() {
        this.disabled = true;
        this.textContent = 'Generating...';
        descriptionText.value = 'Please wait, generating description from AI...';
        const formData = new FormData();
        formData.append('tempFilename', tempFilenameInput.value);
        formData.append('csrf_token', csrfToken);
        try {
            const response = await fetch('ajax_generate_description.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                descriptionText.value = result.description;
                statusMessage.textContent = 'Description generated.';
                statusMessage.style.color = 'green';
            } else { throw new Error(result.error); }
        } catch (error) {
            descriptionText.value = `Error generating description: ${error.message}`;
        } finally {
            this.disabled = false;
            this.textContent = 'Generate Description';
        }
    });

    detailsForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        statusMessage.textContent = 'Saving...';
        const formData = new FormData(this);
        formData.append('csrf_token', csrfToken);
        detailsFieldset.disabled = true;
        try {
            const response = await fetch('ajax_finalize_image.php', { method: 'POST', body: formData });
            const result = await response.json();
            if (result.success) {
                alert(`Successfully saved image "${result.title}"!`);
                window.location.reload();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            statusMessage.textContent = `Save failed: ${error.message}`;
            statusMessage.style.color = 'red';
            detailsFieldset.disabled = false;
        }
    });

    discardBtn.addEventListener('click', async function() {
        if (!confirm('Are you sure you want to discard this image and start over?')) { return; }
        const formData = new FormData();
        formData.append('tempFilename', tempFilenameInput.value);
        formData.append('csrf_token', csrfToken);
        try {
            await fetch('ajax_discard_image.php', { method: 'POST', body: formData });
        } catch (error) {
            console.error('Failed to discard temp file:', error);
        }
        window.location.reload();
    });
});
</script>

<?php
$conn->close();
require_once '../footer.php';
?>
