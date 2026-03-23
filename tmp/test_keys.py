import urllib.request
import urllib.error
import json
import ssl

providers = {
    'gemini': {
        'url': 'https://generativelanguage.googleapis.com/v1beta/openai/models',
        'keys': ['AIzaSyC9sNfJQNSSnWDBHHIyZAQ22aAB2qDQ8_o'],
        'expected_models': ['gemini-3.1-flash-lite-preview', 'gemini-3-flash-preview']
    },
    'groq': {
        'url': 'https://api.groq.com/openai/v1/models',
        'keys': ['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', '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'],
        'expected_models': ['qwen/qwen3-32b', 'llama-3.1-8b-instant', 'openai/gpt-oss-120b', 'openai/gpt-oss-20b']
    },
    'cerebras': {
        'url': 'https://api.cerebras.ai/v1/models',
        'keys': ['csk-3efrrkrx2ceht3e5kr2ejjc24ph9x99rtmmt2hm8khrc3ce2', 'csk-x2hdxmtwerfer2khnn9w64fmxxndmnr9y3c5c4tctt69mv3r'],
        'expected_models': ['llama3.1-8b', 'gpt-oss-120b', 'llama3.1-8b', 'gpt-oss-120b']
    },
    'sambanova': {
        'url': 'https://api.sambanova.ai/v1/models',
        'keys': ['168644c0-747f-4d25-9249-38d6963aadbe', 'ab1fb1a5-1c2f-43a6-94a6-99e316ce429b'],
        'expected_models': ['DeepSeek-V3.1', 'Qwen3-235B', 'DeepSeek-V3.1-Terminus', 'gpt-oss-120b']
    }
}

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

for name, conf in providers.items():
    print(f"=== {name.upper()} ===")
    for key in conf['keys']:
        req = urllib.request.Request(conf['url'])
        req.add_header('Authorization', f'Bearer {key}')
        try:
            with urllib.request.urlopen(req, context=ctx) as response:
                if response.status == 200:
                    data = json.loads(response.read().decode())
                    models = [m['id'] for m in data.get('data', [])]
                    print(f"Key: {key[:8]}... OK")
                    missing = [m for m in conf['expected_models'] if m not in models]
                    if missing:
                        print(f"  Missing expected models: {missing}")
                    print(f"  Available models (subset): {models[:5]}...")
        except urllib.error.HTTPError as e:
            print(f"Key: {key[:8]}... HTTP {e.code}: {e.reason}")
        except Exception as e:
            print(f"Key: {key[:8]}... Error: {e}")
    print()
