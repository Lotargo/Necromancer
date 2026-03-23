import urllib.request
import json
import ssl

url = 'https://api.cerebras.ai/v1/models'
key = 'csk-2xp3kx6dvpjr2hnptmvrh2vymv4ttey85tdxjw293wjpe9hm'

ctx = ssl.create_default_context()
ctx.check_hostname = False
ctx.verify_mode = ssl.CERT_NONE

req = urllib.request.Request(url)
req.add_header('Authorization', f"Bearer {key}")
req.add_header('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36')

try:
    with urllib.request.urlopen(req, context=ctx) as response:
        if response.status == 200:
            data = json.loads(response.read().decode())
            models = [m['id'] for m in data.get('data', [])]
            print(f"CEREBRAS MODELS: {models}")
except Exception as e:
    print(f"Error: {e}")
