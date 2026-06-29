import sys

def fix(path):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    old = "// Alias para compatibilidade com h() (HTML Escaping)\nfunction h($dados) {\n    return sanitizar($dados);\n}\n\n\n// Alias para compatibilidade\nfunction sanitize($dados) {\n    return sanitizar($dados);\n}"
    # The above might have different line endings or spaces.
    # Let's try to match what's in the file.
    
    # Let's use the exact string from the read_files output.
    # Note: I need to be careful about the line endings.
    
    # Content from read_files:
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
    
    # I'll just search for the two function definitions and remove one.
    
    lines = content.splitlines()
    new_lines = []
    skip = False
    for line in lines:
        if "// Alias para compatibilidade com h() (HTML Escaping)" in line:
            skip = True
            continue
        if skip and "function h($dados) {" in line:
            continue
        if skip and "return sanitizar($dados);" in line:
            continue
        if skip and "}" in line:
            # Check if it's the closing brace for h()
            # If we are in skip mode, and we see a brace, we might be done skipping.
            # But there's also the sanitize function.
            # Let's be more specific.
            pass
        
        # This is getting complicated.
        new_lines.append(line)

    # Let's try a very simple way:
    # 1. Find the index of the first h() definition.
    # 2. Find the index of the second h() definition.
    # 3. Remove one.
    
    # Actually, I'll just use the text I saw in the file.
    
    old_block = """// Alias para compatibilidade com h() (HTML Escaping)
function h($dados) {
    return sanitizar($dados);
}


// Alias para compatibilidade
function sanitize($dados) {
    return sanitizar($dados);
}"""
    
    # The file has \r\n probably (Windows)
    old_block_win = old_block.replace('\n', '\r\n')
    
    if old_block_win in content:
        new_content = content.replace(old_block_win, "// Alias para compatibilidade\nfunction sanitize($dados) {\n    return sanitizar($dados);\n}")
    elif old_block in content:
        new_content = content.replace(old_block, "// Alias para compatibilidade\nfunction sanitize($dados) {\n    return sanitizar($dados);\n}")
    else:
        new_content = content
        print("Could not find block")

    with open(path, 'w', encoding='utf-8') as f:
        f.write(new_content)

if __name__ == "__main__":
    fix(sys.argv[1])
