import sys
import re

def fix_cht(file_path):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # We want to replace everything from the beginning of the <script> tag 
    # (if it exists) or just rebuild the end part.
    # In cht/form.php, the script tag is actually missing or broken.
    # Let's look at the file again.
    # The file ends with:
    # 186:             <div class="card-footer" style="display:flex;gap:10px;justify-content:flex-end;">
    # 187:                 <a href="<?php echo APP_URL; ?>documentacao/cht" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
    # 188:                 <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo $editando?'Atualizar':'Salvar'; ?> Certificado</button>
    # 189:             </div>
    # 190:         </div>
    # 191:     </form>
    # 192: </div>
    # 193: 
    # 194: <?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

    # And there is this garbage after it:
    # 195: <script>
    # 196: // Ao carregar, se houver agendamento_id, dispara o carregamento dos dados
    # 197: <?php if (!empty($_GET['agendamento_id'])): ?>
    # 198:     document.addEventListener('DOMContentLoaded', function() {
    # 199:         const select = document.getElementById('embarcacao_id');
    # 200:         if (select && select.value) {
    # 201:             carregarDadosEmbarcacao(select.value);
    # 202:         }
    # 203:     });
    # 204: <?php endif; ?>
    # 205: </script>
    # 206: 
    # 207: <?php require_once __DIR__ . '/../../../includes/footer.php'; ?>

    # The issue is that the file has two footer requires and a broken script.
    
    # Let's find the first occurrence of <?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
    # and replace everything from there to the end.
    
    footer_marker = "<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>"
    
    parts = content.split(footer_marker)
    if len(parts) < 2:
        print("Footer marker not found")
        return

    # parts[0] is the content before the footer.
    # We want to add the script AND the footer.
    
    new_script = """
<script>
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
    
    new_content = parts[0] + new_script
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    print(f"Successfully patched {file_path}")

if __name__ == "__main__":
    if len(sys.argv) > 1:
        fix_cht(sys.argv[1])
