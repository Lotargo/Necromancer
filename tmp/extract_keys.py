import os

base_path = r'f:\lock-rep-stable-projects\Necromancer\tabularium\provisores'
providers = ['gemini', 'groq', 'cerebras', 'sambanova']

for p in providers:
    print(f"--- {p.upper()} ---")
    p_path = os.path.join(base_path, p)
    for f_name in ['url.txt', 'modela.txt', 'claves.txt']:
        f_path = os.path.join(p_path, f_name)
        if os.path.exists(f_path):
            with open(f_path, 'r', encoding='utf-8') as f:
                lines = [l.strip() for l in f.readlines() if l.strip()]
                print(f"{f_name}: {lines}")
        else:
            print(f"{f_name}: MISSING")
    print()
