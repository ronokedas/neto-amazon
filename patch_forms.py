import re
import sys

def patch_file(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Pattern for the value attribute in the forms
    # We want to replace: value="<?php echo $editando ? h($certificado['FIELD']) : ''; ?>"
    # With: value="<?php echo $editando ? h($certificado['FIELD']) : h($preenchimento['FIELD']); ?>"
    
    # Note: We must handle the case where $editando might be used differently or 
    # where the field name is different. But based on the files, it's consistent.
    
    pattern = r'value="\s*<\?php echo \$editando \? h\(\$certificado\['\''(.*?)'\''\]\) : \'\'; \?>"'
    # The above regex might need adjustment depending on exactly how it's written (spaces, etc.)
    # Let's use a more flexible one.
    
    pattern = r'value="\s*<\?php\s+echo\s+\$editando\s+\?\s+h\(\$certificado\['\''(.*?)'\''\]\)\s+:\s+\'\';\s+\?>"'
    replacement = r'value="<?php echo $editando ? h($certificado[\'\1\']) : h($preenchimento[\'\1\']); ?>"'

    # Actually, looking at the files, there are no spaces in some places.
    # Let's use a regex that is more forgiving.
    pattern = r'value="\s*<\?php\s+echo\s+\$editando\s+\?\s+h\(\$certificado\['\''(.*?)'\''\]\)\s+:\s+\'\';\s*\?>"'
    replacement = r'value="<?php echo $editando ? h($certificado[\'\1\']) : h($preenchimento[\'\1\']); ?>"'

    # Let's try a simpler one first and check if it works.
    # The files have: value="<?php echo $editando ? h($certificado['field']) : ''; ?>"
    
    new_content = re.sub(r'value="\s*<\?php\s+echo\s+\$editando\s+\?\s+h\(\$certificado\['\''(.*?)'\''\]\)\s+:\s+\'\';\s*\?>"', 
                         r'value="<?php echo $editando ? h($certificado[\'\1\']) : h($preenchimento[\'\1\']); ?>"', 
                         content)

    if new_content == content:
        print(f"No changes made to {file_path}")
    else:
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Successfully patched {file_path}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage: python patch_forms.py <file_path>")
    else:
        patch_file(sys.argv[1])
