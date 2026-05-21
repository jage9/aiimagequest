import time

import api_client
import config
import data_loader
import db_utils

DESCRIPTION_MODEL = "openai/gpt-latest"


def main():
    print("--- AI Image Quest: Description Generator ---")

    images_to_process = data_loader.get_images_needing_description()
    if not images_to_process:
        print("No images found that need descriptions. All set!")
        return

    print(f"Found {len(images_to_process)} images to process.")

    with db_utils.db_connection() as conn:
        for i, image in enumerate(images_to_process):
            image_id = image["id"]
            image_filename = image["filename"]

            print(f"\n- ({i + 1}/{len(images_to_process)}) Processing '{image_filename}'...")

            image_url = f"{config.BASE_URL}/images/{image_filename}"
            result = api_client.query_model_with_image(
                image_url, config.description_GENERATION_PROMPT, DESCRIPTION_MODEL
            )

            if "error" in result:
                print(f"  API Error for '{image_filename}': {result['error'][:100]}")
            else:
                print("  Generated description, saving to database...")
                db_utils.update_image_description(image_id, result["response_text"], conn=conn)

            time.sleep(1)

    print("\n--- Description generation complete! ---")


if __name__ == "__main__":
    main()
