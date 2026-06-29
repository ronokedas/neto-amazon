import sys

def batch_replace(file_path, replacements):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    new_content = content
    for old_str, new_str in replacements:
        new_content = new_content.replace(old_str, new_str)
        
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print(f"Done replacing in {file_path}")

if __name__ == "__main__":
    path = sys.argv[1]
    # We'll pass replacements as a list of strings in a format we can parse
    # e.g., "old1|new1|old2|new2"
    replacements_raw = sys.argv[2]
    replacements = []
    for i in range(0, len(replacements_raw), 2):
        replacements.append((replacements_raw[i], replacements_raw[i+1]))
    
    batch_replace(path, replacements)
