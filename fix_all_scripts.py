import sys
import re

def patch_script(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    new_script_block = """<script>
function carregarDadosEmbarcacao(embarcacaoId) {
    if (!embarcacaoId) return;
    
    const select = document.getElementById('embarcacao_id');
    const option = select.options[select.selectedIndex];
    
    if (!option) return;
    
    const campos = {
        'nome_embarcacao': 'nome',
        'numero_inscricao': 'numero_inscricao',
        'indicativo_chamada': 'indicativo_chamada',
        'tipo_embarcacao': 'tipo',
        'comprimento_total': 'comprimento_total',
        'comprimento_casco': 'comprimento_casco',
        'boca_moldada': 'boca_moldada',
        'pontal_moldado': 'pontal_moldado',
        'arqueacao_bruta': 'arqueacao_bruta',
        'material_casco': 'material_casco'
    };
    
    for (const [fieldId, dataAttr] of Object.entries(campos)) {
        const input = document.getElementById(fieldId);
        if (input) {
            const value = option.dataset[dataAttr] || '';
            input.value = value;
        }
    }
}

<?php if (!empty($_GET['agendamento_id'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('embarcacao_id');
    if (select && select.value) {
        carregarDadosEmbarcacao(select.value);
    }
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
"""

    # Regex to find the script tag and everything following it
    # We want to replace from <script> to the end of the file
    pattern = r'<script>.*'
    # But we need to be careful about existing tags.
    # Let's use a more specific pattern that looks for the start of the script block.
    
    # Actually, let's just find the first <script> tag.
    match = re.search(r'<script>', content)
    if match:
        start_idx = match.start()
        new_content = content[:start_idx] + new_script_block
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Successfully patched {file_path}")
    else:
        print(f"Could not find <script> in {file_path}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        patch_script(sys.argv[1])
