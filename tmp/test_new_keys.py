import urllib.request
import json
import ssl

providers = {
    'groq': {
        'url': 'https://api.groq.com/openai/v1/models',
        'key': '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'
    },
    'cerebras': {
        'url': 'https://api.cerebras.ai/v1/models',
        'key': 'csk-2xp3kx6dvpjr2hnptmvrh2vymv4ttey85tdxjw293wjpe9hm'
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
