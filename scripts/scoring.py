from thefuzz import fuzz

# The specific phrase our prompt tells the model to use when it can't find an answer.
HONEST_INABILITY_PHRASE = "information not available"

# A list of general phrases that indicate a model is refusing for other reasons.
REFUSAL_PHRASES = ["i cannot", "i am unable", "i'm sorry", "i can't", "as an ai"]

FUZZY_MATCH_THRESHOLD = 90


def _normalize_text(text):
    """Converts text to a standard format for comparison."""
    return str(text).lower().strip()


def score_answer(model_response, correct_answer):
    """
    Scores a model's response using the 4-tier system.
    Returns 'Correct', 'Incorrect', 'Not Found', or 'Refusal'.
    """
    normalized_response = _normalize_text(model_response)
    normalized_correct = _normalize_text(correct_answer)

    # --- THE FIX ---
    # Check if the key phrase is CONTAINED IN the response, not if it's an exact match.
    if HONEST_INABILITY_PHRASE in normalized_response:
        return "Not Found"

    # Check for a general refusal (a failed outcome)
    for phrase in REFUSAL_PHRASES:
        if phrase in normalized_response:
            return "Refusal"

    # Check for an exact containment
    if normalized_correct in normalized_response:
        return "Correct"

    # Check for a high-similarity fuzzy match
    similarity_score = fuzz.partial_ratio(normalized_correct, normalized_response)
    if similarity_score >= FUZZY_MATCH_THRESHOLD:
        return "Correct"

    # If all checks fail, it's incorrect
    return "Incorrect"
