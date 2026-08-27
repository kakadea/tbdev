# Evidências visuais do laboratório — rodada de 27/08/2026

As capturas fornecidas pelo usuário mostram o seguinte estado no laboratório `btw.oldagesubs.com.br`:

| Área | Evidência | Ação prevista |
|---|---|---|
| Home/index.php | HTTP 500 após ativação/login | Rastrear erro no log e corrigir antes de validar o restante |
| Cabeçalho global | Logo grande cortado/sobreposto pelo painel do usuário; ícone de reputação quebrado; campos e navegação desalinhados | Reestruturar header, statusbar e profile com CSS responsivo |
| Rodapé | Texto GPL legado ocupa a tela e botão visual inadequado | Remover texto e botão do rodapé, substituindo por rodapé curto do laboratório |
| Upload | Formulário muito largo, antigo e desalinhado; smilies quebrados; categoria sem opções; envio retorna “You must select a category” | Corrigir layout, assets e provisionar/validar categorias; manter validação server-side |
| Chat | Página apenas informa que Java foi descontinuado | Retirar da navegação e redirecionar com segurança para a home |
| Fóruns | Tela vazia e layout antigo | Retirar da navegação e redirecionar com segurança para a home |
| Links e FAQ | Módulos sem valor previsto para o laboratório | Retirar da navegação e redirecionar com segurança para a home |
| Top 10 | Conteúdo funciona, mas o tema é visualmente quebrado | Herdar o novo layout global e normalizar tabelas responsivas |
| Staff | Conteúdo aparece, mas o cartão do usuário colapsa colunas e textos na vertical | Refatorar cartões/tabela para grid responsivo |
| Admin | Menu funciona, porém é visualmente antigo e pouco legível | Reestruturar menu e validar cada ação sem remover recursos administrativos necessários |

A sequência segura é: diagnosticar o HTTP 500 e os dados de categorias; corrigir o layout global; descontinuar os quatro módulos sem apagar os arquivos; corrigir upload/staff/admin; reconstruir apenas o web do laboratório; e repetir os testes funcionais. Nenhuma alteração em `stg`, `main/master`, banco Hestia ou produção deve ocorrer nesta rodada.
