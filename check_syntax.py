import os
import subprocess

for root, dirs, files in os.walk('c:/sistema'):
    # skip vendor
    if 'vendor' in root:
        continue
    for file in files:
        if file.endswith('.php'):
            path = os.path.join(root, file)
            try:
                result = subprocess.run(['php', '-l', path], capture_output=True, text=True)
                if result.returncode != 0:
                    print(f"Error in {path}:")
                    print(result.stdout.strip())
                    print(result.stderr.strip())
                    print("-" * 40)
            except Exception as e:
                pass
