# Manual simples - Gerar PDF com HTML criado por IA

Este manual é para o seu fluxo no Windows 10:

1. Você pede para uma IA criar uma página HTML.
2. Você salva o HTML dentro da pasta `docs`.
3. Você executa um comando.
4. O sistema gera o PDF automaticamente.

---

## Arquivos principais

HTML editável:

```text
C:\sistema\docs\APRESENTACAO_SISTEMA_AMAZON.html
```

PDF final:

```text
C:\sistema\docs\APRESENTACAO_SISTEMA_AMAZON.pdf
```

Gerador do PDF:

```text
C:\sistema\docs\gerar_apresentacao_pdf.js
```

---

## Comando para gerar o PDF

Abra o terminal do Windows e execute:

```powershell
cd C:\sistema
node .\docs\gerar_apresentacao_pdf.js
```

Depois disso, o PDF será criado ou atualizado aqui:

```text
C:\sistema\docs\APRESENTACAO_SISTEMA_AMAZON.pdf
```

---

## Prompt para pedir para uma IA criar o HTML

Copie o prompt abaixo e envie para a IA.

Troque as partes entre colchetes antes de enviar.

```text
Crie uma página HTML completa, moderna e profissional para ser transformada em PDF A4 no Windows 10 usando Google Chrome.

Importante:
- Eu vou salvar sua resposta como um arquivo .html.
- Depois vou usar um script Node.js que imprime esse HTML em PDF.
- Então o HTML precisa funcionar localmente, sem depender da internet.

Tema do PDF:
[COLOQUE AQUI O TEMA]

Objetivo do PDF:
[EXEMPLO: vender um sistema, apresentar uma proposta, criar um catálogo, fazer um relatório, criar uma apresentação comercial]

Público-alvo:
[COLOQUE AQUI PARA QUEM O PDF SERÁ ENVIADO]

Conteúdo obrigatório:
[COLE AQUI TODO O CONTEÚDO QUE DEVE ENTRAR NO PDF]

Imagens locais, se tiver:
[INFORME OS CAMINHOS DAS IMAGENS, EXEMPLO: ../img/atual.png]

Regras obrigatórias:
- Entregue somente um HTML completo.
- Comece com <!doctype html>.
- Use <html lang="pt-BR">.
- Use <meta charset="utf-8">.
- Coloque todo o CSS dentro da tag <style>.
- Não use bibliotecas externas.
- Não use Bootstrap, Tailwind, React ou Vue.
- Não use imagens da internet.
- Use português do Brasil.
- Crie um visual bonito, moderno, limpo e fácil de ler.
- O PDF deve parecer profissional, não pode parecer um texto simples.
- Use formato A4.
- Cada página do PDF deve usar uma seção:

<section class="sheet">
  conteúdo da página
</section>

- Use este CSS base:

@page {
  size: A4;
  margin: 0;
}

.sheet {
  width: 210mm;
  min-height: 297mm;
  page-break-after: always;
}

- Evite texto cortado.
- Se uma página ficar cheia, crie outra <section class="sheet">.
- Links devem ser feitos com <a href=""> para ficarem clicáveis no PDF.
- Se tiver WhatsApp, use link neste formato:
  https://wa.me/55DDDNUMERO?text=MENSAGEM

Entrega:
- Não explique nada.
- Não use Markdown.
- Não coloque o HTML dentro de bloco ```html.
- Responda apenas com o código HTML completo.
```

---

## Como usar o HTML que a IA criou

Depois que a IA responder:

1. Copie todo o código HTML.
2. Abra este arquivo:

```text
C:\sistema\docs\APRESENTACAO_SISTEMA_AMAZON.html
```

3. Apague o conteúdo antigo.
4. Cole o HTML novo.
5. Salve o arquivo.
6. Rode o comando:

```powershell
cd C:\sistema
node .\docs\gerar_apresentacao_pdf.js
```

Pronto. O PDF será atualizado.

---

## Se quiser gerar outro PDF com outro nome

O jeito mais simples é pedir depois para ajustar o gerador.

Mas, se quiser fazer manualmente, abra este arquivo:

```text
C:\sistema\docs\gerar_apresentacao_pdf.js
```

Procure estas linhas:

```js
const htmlPath = path.join(root, "APRESENTACAO_SISTEMA_AMAZON.html");
const pdfPath = path.join(root, "APRESENTACAO_SISTEMA_AMAZON.pdf");
```

Troque pelos nomes novos.

Exemplo:

```js
const htmlPath = path.join(root, "PROPOSTA_CLIENTE.html");
const pdfPath = path.join(root, "PROPOSTA_CLIENTE.pdf");
```

Depois execute:

```powershell
cd C:\sistema
node .\docs\gerar_apresentacao_pdf.js
```

---

## Requisitos no Windows 10

Para funcionar, precisa ter:

- Node.js instalado;
- Google Chrome ou Microsoft Edge instalado.

Neste computador o Chrome já funcionou para gerar o PDF.

---

## Problemas comuns

### O PDF não atualizou

Rode novamente:

```powershell
cd C:\sistema
node .\docs\gerar_apresentacao_pdf.js
```

### A imagem não apareceu

Use imagem local e caminho relativo.

Exemplo:

```html
<img src="../img/atual.png" alt="Imagem do sistema">
```

### O texto ficou cortado

Peça para a IA dividir o conteúdo em mais páginas usando:

```html
<section class="sheet">
  nova página aqui
</section>
```

