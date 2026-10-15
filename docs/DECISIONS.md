# Decisões de arquitetura e design

## highlight.js — Rejeitado

**Decisão:** Não usar highlight.js para syntax highlighting em code blocks.

**Motivo:** O bundle minificado + gzip excede 30 KB (apenas o core; com linguagens comuns ultrapassa 50 KB). O trade-off não compensa para um chatbot educacional de propósito geral.

**Alternativa adotada:** Code blocks com fundo escuro via Tailwind/CSS puro (`bg-gray-900`) e botão de copiar via JavaScript nativo. Sem dependência externa, sem latência de parse.

---

## Dark mode — Estratégia `class`

**Decisão:** `darkMode: 'class'` no Tailwind, controlado por `localStorage` com fallback para `prefers-color-scheme`.

**Motivo:** Permite que o usuário sobrescreva a preferência do sistema sem recarregar a página. Um `<script>` inline no `<head>` aplica a classe antes do primeiro paint, evitando FOUC.

---

## Agrupamento de conversas na sidebar

**Decisão:** Agrupar por "Hoje", "Ontem", "Últimos 7 dias" e "Mais antigas" no helper `ConversationGrouper`.

**Motivo:** Segue o padrão estabelecido pelo ChatGPT e similares, que os usuários já conhecem. A lógica fica em uma classe de suporte testável e não polui o controller.

---

## Rename inline na sidebar

**Decisão:** Rename feito via Alpine.js com `x-data` inline em cada item de conversa, chamando `PATCH /conversations/{id}` por fetch.

**Motivo:** Evita recarregamento de página e mantém o estado de scroll. A renderização server-side inicial garante que o título correto aparece mesmo sem JavaScript.

---

## Ordering de mensagens — remoção do `orderBy` do relacionamento

**Decisão:** Removido `->orderBy('created_at')` do relacionamento `Conversation::messages()`. O controller de exibição usa `->orderBy('id')` explicitamente.

**Motivo:** O `orderBy` no relacionamento acumulava com cláusulas adicionadas pelo controller, gerando `ORDER BY created_at ASC, id DESC`. Em produção, timestamps distintos faziam o `->reverse()` do payload inverter a ordem das mensagens, resultando em respostas repetidas (sempre a primeira resposta). Testes com `freezeTime()` mascaravam o bug por timestamps iguais.

---

## Sistema de tradução — `lang/pt_BR.json`

**Decisão:** Arquivo JSON de tradução para todas as strings dos formulários de autenticação e perfil.

**Motivo:** Laravel Breeze gera strings em inglês. Um arquivo JSON centraliza as traduções sem precisar sobrescrever views ou usar pacotes externos.

---

## "Nova conversa" sem duplicatas

**Decisão:** `ConversationController::store()` redireciona para uma conversa existente sem título e sem mensagens em vez de criar outra.

**Motivo:** Cliques duplos ou rápidos geravam registros vazios na sidebar. A deduplicação acontece por `whereNull('title')->doesntHave('messages')` — assim que o usuário enviar a primeira mensagem, o próximo clique em "Nova conversa" cria uma conversa de fato nova.

---

## Regenerar resposta — rota própria, sem reuso do MessageController

**Decisão:** `RegenerateController` apaga a última mensagem assistant, reconstrói o payload sem ela e chama a IA novamente. Rota `POST /conversations/{conversation}/regenerate` com `throttle:chat`.

**Motivo:** A lógica de regenerar é diferente o suficiente de enviar (não salva user message, não gera título, apaga a resposta anterior) para justificar um controller separado. Reutiliza a mesma Policy (`view`) e o mesmo rate limit.

---

## Botão "Regenerar" — visibilidade

**Decisão:** No desktop (`md:`), o botão "Regenerar" aparece com `opacity-0` e muda para `opacity-100` no hover do wrapper pai (`group`). No mobile (sem `hover: hover`), fica com `opacity-60` o tempo todo.

**Motivo:** No mobile não existe hover; esconder completamente o botão tornaria a feature inacessível sem teclado externo. A opacidade reduzida mantém a interface limpa sem sacrificar a usabilidade.
