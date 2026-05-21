import time

import api_client
import config
import data_loader
import db_utils
import scoring


def main():
    print("--- AI Image Quest Benchmark Runner ---")

    models_to_run = data_loader.get_models_to_run()
    if not models_to_run:
        print("No models found in the database. Exiting.")
        return

    print(f"Found {len(models_to_run)} models to test.")

    for model in models_to_run:
        model_id = model["id"]
        model_identifier = model["api_identifier"]
        print(f"\n--- Processing Model: {model['provider']} ({model_identifier}) ---")

        pending_questions = data_loader.get_pending_questions_for_model(model_id)
        if not pending_questions:
            print("No pending questions for this model. Moving to the next.")
            continue

        print(f"Found {len(pending_questions)} pending questions.")

        with db_utils.db_connection() as conn:
            for i, question in enumerate(pending_questions):
                question_id = question["question_id"]
                image_filename = question["filename"]

                print(
                    f"  - ({i + 1}/{len(pending_questions)}) Testing Q#{question_id}"
                    f" on '{image_filename}'..."
                )

                image_url = f"{config.BASE_URL}/images/{image_filename}"
                prompt_text = config.PROMPT_TEMPLATES[config.CURRENT_PROMPT_VERSION].format(
                    question_text=question["question"]
                )

                result = api_client.query_model_with_image(image_url, prompt_text, model_identifier)

                if "error" in result:
                    print(f"    API Error: {result['error'][:100]}")
                    db_utils.log_error(question_id, model_id, result["error"], conn=conn)
                else:
                    score = scoring.score_answer(
                        result["response_text"], question["correct_answer"]
                    )
                    print(f"    Success! Score: {score}")
                    db_utils.log_run(
                        question_id,
                        model_id,
                        result,
                        score,
                        config.CURRENT_PROMPT_VERSION,
                        conn=conn,
                    )

                time.sleep(1)

    print("\n--- Benchmark run complete! ---")


if __name__ == "__main__":
    main()
