import sys

def fix_cnbl_js(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # The current file has a broken structure at the end.
    # We want to find the end of carregarDadosEmbarcacao and clean up.
    
    # The function starts at line 490.
    # It contains the loop for fields, then checkboxes, then the broken PHP block.
    
    # Let's find the end of the function properly.
    # The function ends at line 534.
    
    # I'll just replace the whole <script> block.
    
    script_start_marker = '<script>'
    script_end_marker = '</script>'
    
    start_idx = content.find(script_start_marker)
    end_idx = content.find(script_end_marker)
    
    if start_idx == -1 or end_idx == -1:
        print("Markers not found")
        return

    new_script = """<script>
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
    
    const tipoNavegacao = (option.dataset.tipo_navegacao || '').split(',').map(s => s.trim()).filter(Boolean);
    document.querySelectorAll('input[name="tipo_navegacao[]"]').forEach(cb => {
        cb.checked = tipoNavegacao.includes(cb.value);
    });
    
    const areaNavegacao = (option.dataset.area_navegacao || '').split(',').map(s => s.trim()).filter(Boolean);
    document.querySelectorAll('input[name="area_navegacao[]"]').forEach(cb => {
        cb.checked = areaNavegacao.includes(cb.value);
    });
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
"""
    
    new_content = content[:start_idx] + new_script + content[end_idx + len(script_end_marker):]
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print(f"Successfully fixed {file_path}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        fix_cnbl_js(sys.argv[1])
