import sys

def fix_functions(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        lines = f.readlines()

    new_lines = []
    i = 0
    while i < len(lines):
        # Detectar o início do bloco que queremos remover (a duplicata da sanitize)
        if "// Alias para compatibilidade" in lines[i] and "function sanitize($dados)" in lines[i+1]:
            # Pular até o fim do bloco (fechamento da função)
            while i < len(lines) and "}" not in lines[i]:
                i += 1
            i += 1 # Pular o }
            continue
        
        new_lines.append(lines[i])
        i += 1

    with open(file_path, 'w', encoding='utf-8') as f:
        f.writelines(new_lines)
    print(f"Fixed {file_path}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        fix_functions(sys.argv[1])
