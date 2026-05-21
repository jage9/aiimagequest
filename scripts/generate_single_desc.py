import os
import sys

from dotenv import load_dotenv

load_dotenv(os.path.join(os.path.dirname(__file__), ".env"))

import api_client  # noqa: E402
import config  # noqa: E402

DESCRIPTION_MODEL = "openai/gpt-4o-latest"

if __name__ == "__main__":
    if len(sys.argv) < 2:
        sys.exit(1)

    image_path = sys.argv[1]

    try:
        base64_image = api_client.encode_image(image_path)
        result = api_client.query_model_with_base64(
            base64_image, config.description_GENERATION_PROMPT, DESCRIPTION_MODEL
        )
        if "error" in result:
            sys.exit(1)
        print(result["response_text"])
        sys.stdout.flush()
    except Exception:
        sys.exit(1)
