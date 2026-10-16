# Chatbot — Laravel + Z.AI (GLM)

> **EN:** A full-stack chatbot built with Laravel 13 + Blade + Alpine.js, backed by Z.AI's GLM language models. Multi-conversation history, Markdown rendering, real authentication, SQLite by default.

---

## O que é

Aplicação web de chatbot monolítica com histórico de conversas persistido. Cada usuário tem suas próprias conversas (isoladas por política de autorização). As respostas vêm de um LLM real via [Z.AI](https://z.ai) (modelos GLM, API compatível com OpenAI).

## Funcionalidades

- Cadastro e login com validação de senha forte (Laravel Breeze)
- Múltiplas conversas por usuário, com histórico permanente
- Título automático gerado a partir da primeira mensagem (sem pontuação final)
- Agrupamento de conversas na sidebar por data: Hoje, Ontem, Últimos 7 dias, Mais antigas
- Renomear e excluir conversas diretamente na sidebar (inline, sem recarregar)
- Renderização segura de Markdown nas respostas (CommonMark, XSS mitigado)
- Botão de copiar por resposta da IA e por bloco de código
- Regenerar última resposta da IA com um clique (histórico sem a resposta anterior)
- Cards de sugestões no estado vazio para iniciar conversa rapidamente
- Tema claro/escuro com persistência em `localStorage` e respeito a `prefers-color-scheme`
- Interface responsiva: sidebar recolhível, safe area no mobile, 44px de área de toque
- Rate limit de 20 mensagens/minuto por usuário
- Suporte a streaming SSE (desativado por padrão; veja Variáveis de Ambiente)

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.4, Laravel 13 |
| Auth | Laravel Breeze (Blade stack) |
| Frontend | Blade, Alpine.js, Tailwind CSS |
| Banco | SQLite (zero configuração) |
| IA | Z.AI (GLM-4.5-flash) |
| Markdown | league/commonmark |
| Testes | PHPUnit, Http::fake() |
| CI | GitHub Actions |

## Diagrama de Arquitetura

```mermaid
graph LR
    Browser -->|HTTPS| Laravel
    Laravel -->|Auth + Policy| DB[(SQLite)]
    Laravel -->|ZaiClient| ZAI[Z.AI API<br>GLM-4.5-flash]
    ZAI -->|choices[0].message.content| Laravel
    Laravel -->|JSON / SSE| Browser
    Laravel -->|Conversations + Messages| DB
```

## Como rodar

```bash
# 1. Clonar e instalar dependências
git clone https://github.com/diiegoapp/chatbot
cd chatbot
composer install

# 2. Configurar o ambiente
cp .env.example .env
php artisan key:generate

# 3. Banco de dados
touch database/database.sqlite
php artisan migrate

# 4. Adicionar sua chave Z.AI no .env
#    ZAI_API_KEY=sua_chave_aqui

# 5. Build do frontend
npm install && npm run build

# 6. Iniciar o servidor
php artisan serve
```

Acesse: http://localhost:8000

## Variáveis de Ambiente

| Variável | Padrão | Descrição |
|---|---|---|
| `ZAI_API_KEY` | — | **Obrigatório.** Chave da Z.AI |
| `ZAI_BASE_URL` | `https://api.z.ai/api/paas/v4` | Endpoint da API |
| `ZAI_MODEL` | `glm-4.5-flash` | Modelo GLM a usar |
| `ZAI_TIMEOUT` | `60` | Timeout em segundos |
| `ZAI_MAX_TOKENS` | `1024` | Máximo de tokens por resposta |
| `ZAI_THINKING` | `disabled` | Raciocínio interno do modelo |
| `CHATBOT_STREAMING` | `false` | Habilita SSE streaming |
| `CHATBOT_CONTEXT_WINDOW` | `20` | Mensagens enviadas como contexto |
| `CHATBOT_MAX_MESSAGE_LENGTH` | `4000` | Tamanho máximo de mensagem (chars) |

## Rodando os testes

```bash
php artisan test
```

Para verificar o estilo de código:

```bash
./vendor/bin/pint --test
```

## Smoke test da API (opcional)

Após preencher a `ZAI_API_KEY` no `.env`:

```bash
php scripts/smoke-test-ai.php
```

## Decisões técnicas

Veja [docs/DECISIONS.md](docs/DECISIONS.md).

## Limitações conhecidas

- **Streaming com `php artisan serve`**: O servidor built-in do PHP pode ter buffering que impede o streaming em tempo real. Por isso o streaming está desativado por padrão (`CHATBOT_STREAMING=false`). Para ativar, use Nginx/FPM ou Apache em produção.
- **Conta gratuita Z.AI**: O modelo `glm-4.7-flash` retorna HTTP 429 frequentemente no plano gratuito. O modelo `glm-4.5-flash` (padrão) funciona de forma mais estável.
- **Sem fila**: Respostas da IA são síncronas. Para alto volume, mover para filas (Laravel Horizon).

## Roadmap

- [ ] Suporte a upload de arquivos (RAG simples)
- [ ] Múltiplos modelos configuráveis por conversa
- [ ] Exportar conversa como PDF/Markdown
- [ ] Deploy one-click (Railway, Fly.io)

## Screenshots

<!-- Adicione screenshots após rodar o projeto localmente -->
<!-- ![Login](docs/screenshots/login.png) -->
<!-- ![Chat](docs/screenshots/chat.png) -->

## Licença

MIT — veja [LICENSE](LICENSE).
