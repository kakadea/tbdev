# Componentes legados e decisão de modernização

## Resumo

A modernização não trata todos os arquivos antigos da mesma maneira. Código que recebe requisições, grava dados, executa arquivos ou controla autenticação recebe prioridade de segurança. CSS, imagens e placeholders antigos são preservados quando são apenas cosméticos ou fazem parte do contrato visual, evitando uma grande regressão sem teste visual.

| Componente | Estado | Decisão |
|---|---|---|
| `captcha/` | Ativo opcionalmente em login, cadastro e recuperação | Mantido e modernizado. A geração usa `random_int`, a sessão é compartilhada pela camada de segurança, a validação aceita somente seis letras e expira em 15 minutos, e os endpoints de atualização/verificação não aceitam mais GET para validar resposta. |
| `install/` | Instalador web destrutivo e incompatível com configuração por ambiente | Removido do checkout executável. A instalação do laboratório usa `SQL/tb.sql`, migrações explícitas e `.env.lab`; não há mais fluxo web capaz de apagar tabelas ou sobrescrever `announce.php`. |
| `javairc/` | Arquivo binário Java applet sem caller funcional em navegador moderno | Payload removido. `chat.php` permanece como rota estável, mas informa que o applet foi desativado até existir uma implementação de chat moderna e testada. |
| `torrents/` | Armazenamento de `.torrent` necessário ao runtime | Mantido como conceito, não como código da aplicação. O Docker usa `TBDEV_TORRENT_DIR` em volume externo; o `.htaccess` foi atualizado para negar acesso e desativar listagem. |
| `logs/` | Diretório de logs legado | Mantido como ponto de compatibilidade, mas o runtime usa `TBDEV_LOG_DIR`/driver do container. O `.htaccess` foi atualizado para negar acesso e desativar listagem. |
| CSS e imagens | Visual antigo, sem lógica de banco ou sessão | O stylesheet padrão recebeu uma camada responsiva/acessível que preserva seletores legados. A reescrita visual completa continua separada, com screenshots/regressão funcional, porque alterar tabelas, classes e dimensões pode quebrar formulários e páginas administrativas. |
| `videoformats.php` e idioma | Página antiga baseada em tabela e arquivo indevido `lang/en/videoformats.php` | A página foi reestruturada em seções semânticas responsivas usando `lang/en/lang_videoformats.php`; o arquivo PHP indevido dentro do diretório de idiomas foi removido. |
| PHP restante | Mistura de front controllers, handlers e rotinas de manutenção | Continua em migração incremental. O código crítico deve ser convertido para POST/CSRF, validação tipada e prepared statements antes de qualquer cutover. |

## CAPTCHA

O CAPTCHA continua desligado por padrão na configuração atual. Quando habilitado, o container precisa da extensão GD, que foi adicionada ao `Dockerfile`. O teste CLI `tests/captcha_test.php` cobre formato, resposta correta, comparação sem distinção de caixa, entrada em array e expiração.

Os endpoints de CAPTCHA são deliberadamente pequenos: `newsession.php` cria um desafio via POST e responde sem cache; `image_req.php` retorna apenas o fragmento HTML controlado da imagem; `process.php` aceita somente POST e devolve `1` ou `0` sem refletir a entrada. A validação usada pelos handlers de login, cadastro e recuperação é a mesma função centralizada.

## Instalador

O instalador antigo não deve ser reativado. Mesmo que o arquivo de lock existisse, o fluxo aceitava valores web, usava `mysql_*`, podia executar `DROP TABLE`, criar banco, gravar configuração e sobrescrever o código do tracker. O procedimento seguro é aplicar o schema em banco descartável, aplicar as migrações em ordem e configurar segredos fora do Git.

## JavaIRC

A rota `chat.php` não carrega mais applets, arquivos `.jar`, `.cab` ou o arquivo binário arquivado. A navegação global pode continuar apontando para a rota para evitar link quebrado, mas o usuário recebe uma mensagem clara. Uma futura substituição deve ser um componente separado, com autenticação, limites, persistência e testes próprios; não se deve tentar ressuscitar o applet Java.

## Frontend e compatibilidade de navegador

O cabeçalho comum agora usa HTML5, `lang`, charset, viewport e referrer explícitos. O stylesheet padrão mantém as classes antigas, mas corrige larguras fixas, cria comportamento responsivo para navegação, formulários, tabelas e posts, e adiciona estados de foco. `popup.js`, `show_hide.js` e `bbcode2text.js` foram reescritos sem APIs exclusivas do Internet Explorer ou variáveis globais implícitas. O teste central rejeita o retorno de `document.selection`, `ActiveXObject`, `navigator.appVersion` e `document.all`.

## Próxima validação

Nenhuma dessas alterações foi considerada funcionalmente aprovada ainda. O laboratório descartável deve validar a geração de imagem com GD, sessões de CAPTCHA, login/cadastro/recuperação, ausência do instalador na imagem, ausência do payload JavaIRC, bloqueio HTTP de `torrents/` e `logs/`, além de confirmar que os arquivos `.torrent` continuam fora da imagem e acessíveis somente pelo fluxo de download autorizado.
