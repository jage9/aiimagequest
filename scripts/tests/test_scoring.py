import pytest
from scoring import score_answer


class TestCorrect:
    def test_exact_match(self):
        assert score_answer("Red", "red") == "Correct"

    def test_case_insensitive(self):
        assert score_answer("TOYOTA", "toyota") == "Correct"

    def test_answer_contained_in_response(self):
        assert score_answer("The car is definitely red in color", "red") == "Correct"

    def test_fuzzy_match_above_threshold(self):
        assert score_answer("Toyata Camry", "Toyota Camry") == "Correct"

    def test_numeric_answer(self):
        assert score_answer("There are 42 items visible", "42") == "Correct"


class TestIncorrect:
    def test_wrong_answer(self):
        assert score_answer("Blue", "red") == "Incorrect"

    def test_unrelated_response(self):
        assert score_answer("The sky is clear today", "red car") == "Incorrect"


class TestNotFound:
    def test_exact_phrase(self):
        assert score_answer("Information not available", "anything") == "Not Found"

    def test_phrase_embedded_in_longer_response(self):
        assert score_answer("Sorry, information not available for this image.", "anything") == "Not Found"

    def test_case_insensitive_phrase(self):
        assert score_answer("INFORMATION NOT AVAILABLE", "anything") == "Not Found"


class TestRefusal:
    def test_i_cannot(self):
        assert score_answer("I cannot help with that request.", "answer") == "Refusal"

    def test_i_am_unable(self):
        assert score_answer("I am unable to assist.", "answer") == "Refusal"

    def test_as_an_ai(self):
        assert score_answer("As an AI, I don't have visual access.", "answer") == "Refusal"

    def test_im_sorry(self):
        assert score_answer("I'm sorry, I can't do that.", "answer") == "Refusal"

    def test_i_cant(self):
        assert score_answer("I can't see the image clearly.", "answer") == "Refusal"


class TestPrecedence:
    def test_not_found_takes_priority_over_incorrect(self):
        assert score_answer("information not available", "red") == "Not Found"

    def test_refusal_takes_priority_over_incorrect(self):
        assert score_answer("I cannot answer questions about red cars.", "blue") == "Refusal"
