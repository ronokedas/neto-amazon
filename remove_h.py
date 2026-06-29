import sys

def remove_duplicate_h(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    new_lines = []
    i = 0
    while i < len(lines):
        # Looking for the block starting with "// Alias para compatibilidade com h() (HTML Escaping)"
        if "// Alias para compatibilidade com h() (HTML Escaping)" in lines[i]:
            # Skip this block
            # It's:
            # 14: // Alias para compatibilidade com h() (HTML Escaping)
            # 15: function h($dados) {
            # 16:     return sanitizar($dados);
            # 17: }
            # 18: 
            # 19: 
            # 20: // Alias para compatibilidade
            # 21: function sanitize($dados) {
            # 22:     return sanitizar($dados);
            # 23: }
            # 24: 
            i += 10 # Skip roughly this many lines
            continue
        
        new_lines.append(lines[i])
        i += 1

    with open(file_path, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    print(f"Processed {file_path}")

if __name__ == "__main de "__main__":
    if len(sys.argv) > 1:
        remove_duplicate_h(sys.argv[1])
