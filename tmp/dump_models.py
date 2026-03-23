import urllib.request
import json
import ssl

providers = {
    'gemini': {
        'url': 'https://generativelanguage.googleapis.com/v1beta/openai/models',
        'key': 'AIzaSyC9sNfJQNSSnWDBHHIyZAQ22aAB2qDQ8_o'
    },
    'sambanova': {
        'url': 'https://api.sambanova.ai/v1/models',
        'key': '168644c0-747f-4d25-9249-38d6963aadbe'
    }
}

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

for name, conf in providers.items():
    print(f"=== {name.upper()} ===")
    req = urllib.request.Request(conf['url'])
    req.add_header('Authorization', f"Bearer {conf['key']}")
    try:
        with urllib.request.urlopen(req, context=ctx) as response:
            if response.status == 200:
                data = json.loads(response.read().decode())
                models = [m['id'] for m in data.get('data', [])]
                print(f"Available models: {models}")
    except Exception as e:
        print(f"Error: {e}")
    print()
