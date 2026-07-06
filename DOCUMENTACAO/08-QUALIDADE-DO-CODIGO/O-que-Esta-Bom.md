# O que Está Bem Implementado

Este arquivo documenta as decisões corretas do sistema atual que devem ser mantidas ou inspirar a nova stack.

## Padrões que funcionam bem
- A organização unificada em `modules/` que agrupa visualmente cada escopo de negócio, facilitando a navegação mental do sistema.
- A decisão de centralizar a comunicação de banco de dados via objeto instanciado (`$pdo`), blindando minimamente contra o antigo `mysql_query` e protegendo contra vazamentos básicos via _Prepared Statements_.

## Lógicas de negócio bem implementadas
- A usabilidade da Assinatura via Canvas: a mecânica de permitir o usuário assinar de forma tátil/Touch através de um Hash único URL (sem precisar forçá-lo a criar contas de login e senha na plataforma).

## Convenções consistentes
- Nomenclatura das tabelas no banco de dados (`certificados_csn`, `certificados_cnbl`) facilitam e induzem uma correta estruturação mental de polimorfismo que deve ser levada ao PrismaORM.
- Nomenclatura previsível dos arquivos base: todo módulo possui pelo menos um `index.php`, um `form.php` e um `actions.php`.

## O que a nova stack deve preservar
- A experiência "Frictionless" (sem atrito) do cliente final ao receber as propostas ou links de assinatura. 
- A granularidade flexível do administrador frente aos cancelamentos e listagens globais que a interface atual permite visualizar rapidamente.