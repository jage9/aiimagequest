import os
from contextlib import contextmanager

import mysql.connector
from dotenv import load_dotenv

load_dotenv()


def get_db_connection():
    try:
        return mysql.connector.connect(
            host=os.getenv("DB_HOST"),
            user=os.getenv("DB_USER"),
            password=os.getenv("DB_PASSWORD"),
            database=os.getenv("DB_NAME"),
        )
    except mysql.connector.Error as err:
        print(f"Error connecting to database: {err}")
        return None


@contextmanager
def db_connection():
    conn = get_db_connection()
    try:
        yield conn
    finally:
        if conn and conn.is_connected():
            conn.close()


def _execute(sql, values, conn=None):
    _conn = conn or get_db_connection()
    if not _conn:
        return False
    cursor = None
    try:
        cursor = _conn.cursor()
        cursor.execute(sql, values)
        _conn.commit()
        return True
    except mysql.connector.Error as err:
        print(f"Database error: {err}")
        return False
    finally:
        if cursor:
            cursor.close()
        if conn is None and _conn.is_connected():
            _conn.close()


def log_run(question_id, model_id, run_data, score, prompt_version, conn=None):
    sql = """
        INSERT INTO runs (question_id, model_id, response, score, prompt_version,
                          latency_ms, prompt_tokens, completion_tokens, cost)
        VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
    """
    values = (
        question_id, model_id, run_data['response_text'], score, prompt_version,
        run_data['latency_ms'], run_data['prompt_tokens'],
        run_data['completion_tokens'], run_data['cost'],
    )
    return _execute(sql, values, conn)


def log_error(question_id, model_id, error_message, conn=None):
    sql = "INSERT INTO error_logs (question_id, model_id, error_message) VALUES (%s, %s, %s)"
    return _execute(sql, (question_id, model_id, error_message), conn)


def update_image_description(image_id, description_text, conn=None):
    sql = "UPDATE images SET description = %s WHERE id = %s"
    result = _execute(sql, (description_text, image_id), conn)
    status = "Updated" if result else "Failed to update"
    print(f"    {status} description for image ID: {image_id}")
    return result
