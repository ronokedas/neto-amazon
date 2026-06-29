<?php
$f = 'c:/sistema/modules/documentacao/cht/form.php';
$c = file_get_contents($f);
$c = str_replace(
"    <div class=\"tabela-header\">\r\n        <h2><i class=\"fas fa-file-certificate\"></i> <?php echo \$editando?'Editar':'Novo'; ?> Certificado de Homologação Técnica</h2>\r\n        <a href=\"<?php echo APP_URL; ?>documentacao/cht\" class=\"btn btn-secondary\"><i class=\"fas fa-arrow-left\"></i> Voltar</a>\r\n    </div>\r\n\r\n\r\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
"    <div class=\"tabela-header\">\r\n        <h2><i class=\"fas fa-file-certificate\"></i> <?php echo \$editando?'Editar':'Novo'; ?> Certificado de Homologação Técnica</h2>\r\n        <a href=\"<?php echo APP_URL; ?>documentacao/cht\" class=\"btn btn-secondary\"><i class=\"fas fa-arrow-left\"></i> Voltar</a>\r\n    </div>\r\n\r\n<?php\r\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
$c);
$c = str_replace(
"    }\r\n}\r\n\r\n    <?php if (\$editando && \$cht['assinado']): ?>",
"    }\r\n}\r\n?>\r\n    <?php if (\$editando && \$cht['assinado']): ?>",
$c);
$c = str_replace(
"        <div class=\"card mb-3\">\r\n\r\n// Ao carregar, se houver agendamento_id, dispara o carregamento dos dados\r\n<?php if (!empty(\$_GET['agendamento_id'])): ?>\r\n    document.addEventListener('DOMContentLoaded', function() {\r\n        const select = document.getElementById('embarcacao_id');\r\n        if (select && select.value) {\r\n            carregarDadosEmbarcacao(select.value);\r\n        }\r\n    });\r\n<?php endif; ?>\r\n\r\n            <div class=\"card-footer\"",
"        <div class=\"card mb-3\">\r\n            <div class=\"card-footer\"",
$c);
file_put_contents($f, $c);
