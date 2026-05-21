# Import our database connection utility
import db_utils

def get_models_to_run():
    """
    Fetches a list of all models from the database.
    Returns a list of dictionaries, e.g., [{'id': 1, 'api_identifier': 'openai/gpt-5'}].
    """
    conn = db_utils.get_db_connection()
    if not conn:
        return []

    models = []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute("SELECT id, api_identifier, provider FROM models")
        models = cursor.fetchall()
    except Exception as e:
        print(f"Error fetching models: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()
    return models

def get_pending_questions_for_model(model_id):
    """
    Finds all questions that do not have a result in the 'runs' table for a given model_id.
    This makes the benchmark resumable.
    Returns a list of dictionaries containing question and image info.
    """
    conn = db_utils.get_db_connection()
    if not conn:
        return []

    # This SQL query is the core of the "resumable" logic.
    # It uses a LEFT JOIN to find questions that have no matching run.
    sql = """
        SELECT 
            q.id AS question_id, 
            q.question, 
            q.correct_answer,
            i.id AS image_id,
            i.filename
        FROM questions q
        JOIN images i ON q.image_id = i.id
        LEFT JOIN runs r ON q.id = r.question_id AND r.model_id = %s
        WHERE r.id IS NULL
    """
    
    pending_questions = []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute(sql, (model_id,))
        pending_questions = cursor.fetchall()
    except Exception as e:
        print(f"Error fetching pending questions for model {model_id}: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()
    return pending_questions

def get_images_needing_description():
    """
    A helper function that finds all images missing an accessibility description.
    Useful for a one-time script to populate those descriptions.
    """
    conn = db_utils.get_db_connection()
    if not conn:
        return []

    sql = "SELECT id, filename FROM images WHERE description IS NULL OR description = ''"
    images_to_process = []
    try:
        cursor = conn.cursor(dictionary=True)
        cursor.execute(sql)
        images_to_process = cursor.fetchall()
    except Exception as e:
        print(f"Error fetching images needing description: {e}")
    finally:
        if conn.is_connected():
            cursor.close()
            conn.close()
    return images_to_process