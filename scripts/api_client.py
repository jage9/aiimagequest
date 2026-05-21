import requests
import json
import base64
import os
import time

def encode_image(image_path):
    with open(image_path, "rb") as image_file:
        return base64.b64encode(image_file.read()).decode('utf-8')

def query_model_with_image(image_url, prompt_text, model_identifier):
    """
    Queries a model via OpenRouter with an image URL and a text prompt.
    Returns a dictionary with the response data or an error message.
    """
    api_key = os.getenv("OPENROUTER_API_KEY")

    start_time = time.time()
    
    response = requests.post(
      url="https://openrouter.ai/api/v1/chat/completions",
      headers={ "Authorization": f"Bearer {api_key}", "HTTP-Referer": "https://aiimagequest.com", "X-Title": "AI Image Quest" },
      data=json.dumps({
        "model": model_identifier,
        "temperature": 0, "max_tokens": 500, "usage": {"include": True},
        "messages": [
          { "role": "user", "content": [
              {"type": "text", "text": prompt_text},
              {"type": "image_url", "image_url": { "url": image_url }}
          ]}
        ]
      }),
      timeout=30,
    )

    end_time = time.time()
    latency_ms = int((end_time - start_time) * 1000)

    if response.status_code != 200:
        return {"error": response.text}

    data = response.json()

    return {
        "response_text": data['choices'][0]['message']['content'],
        "prompt_tokens": data['usage']['prompt_tokens'],
        "completion_tokens": data['usage']['completion_tokens'],
        "cost": data['usage']['cost'],
        "latency_ms": latency_ms
    }

def query_model_with_base64(base64_image, prompt_text, model_identifier):
    """
    Queries a model via OpenRouter with Base64 image data and a text prompt.
    Returns a dictionary with the response data or an error message.
    """
    api_key = os.getenv("OPENROUTER_API_KEY")

    start_time = time.time()

    response = requests.post(
      url="https://openrouter.ai/api/v1/chat/completions",
      headers={ "Authorization": f"Bearer {api_key}", "HTTP-Referer": "https://aiimagequest.com", "X-Title": "AI Image Quest" },
      data=json.dumps({
        "model": model_identifier,
        "temperature": 0, "max_tokens": 500, "usage": {"include": True},
        "messages": [
          { "role": "user", "content": [
              {"type": "text", "text": prompt_text},
              {"type": "image_url", "image_url": {"url": f"data:image/jpeg;base64,{base64_image}"}}
          ]}
        ]
      }),
      timeout=30,
    )
    
    end_time = time.time()
    latency_ms = int((end_time - start_time) * 1000)

    if response.status_code != 200:
        return {"error": response.text}

    data = response.json()
    
    return {
        "response_text": data['choices'][0]['message']['content'],
        "prompt_tokens": data['usage']['prompt_tokens'],
        "completion_tokens": data['usage']['completion_tokens'],
        "cost": data['usage']['cost'],
        "latency_ms": latency_ms
    }