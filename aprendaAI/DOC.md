# AprendaAI - Sistema de Aprendizado Adaptativo

## Visão Geral

AprendaAI é uma plataforma de aprendizado adaptativo projetada para personalizar a experiência educacional de cada estudante com base em seu desempenho, preferências e estilo de aprendizado. O sistema utiliza inteligência artificial para adaptar o conteúdo, gerar explicações personalizadas e recomendar roteiros de estudo otimizados.

## Arquitetura do Sistema

O AprendaAI é construído sobre o framework Laravel (PHP) com uma arquitetura de API RESTful que suporta front-ends modernos construídos com React (via Inertia.js). O sistema utiliza inteligência artificial via integração com o Google Gemini para personalização de conteúdo.

### Tecnologias Principais

- **Backend**: Laravel, PHP 8.4
- **Frontend**: React.js, TypeScript (via Inertia.js)
- **Banco de Dados**: MySQL/SQLite (para testes)
- **IA**: Google Gemini
- **Cache**: Redis (para explicações e recomendações)
- **Autenticação**: Laravel Sanctum (API tokens)

## Funcionalidades Principais

### 1. Aprendizado Adaptativo

- **Seleção Inteligente de Questões**: Seleciona questões com base no nível de proficiência do aluno
- **Análise de Desempenho**: Avalia padrões de resposta, tempo gasto e taxa de acerto
- **Ajuste Dinâmico de Dificuldade**: Aumenta ou diminui a dificuldade com base no desempenho

### 2. Explicações Personalizadas

- **Explicações Adaptativas**: Gera explicações adaptadas ao nível de conhecimento do aluno
- **Cache Inteligente**: Armazena explicações para acesso rápido e economia de recursos
- **Feedback Contextual**: Fornece feedback específico baseado nos erros cometidos

### 3. Planos de Estudo

- **Criação de Planos**: Permite a criação manual ou automática de planos de estudo
- **Sessões Programadas**: Organiza o estudo em sessões com tópicos específicos
- **Adaptação Dinâmica**: Ajusta o plano com base no progresso e desempenho

### 4. Análise de Desempenho

- **Insights de Aprendizado**: Gera insights sobre pontos fortes e fracos do estudante
- **Recomendações**: Sugere próximos passos e recursos adicionais
- **Visualização de Progresso**: Mostra o progresso ao longo do tempo

## Modelo de Dados

### Entidades Principais

#### Usuários e Progresso
- `User`: Informações do usuário, incluindo preferências de aprendizado
- `UserProgress`: Rastreia o progresso do usuário em cada tópico
- `UserAnswer`: Registra as respostas dos usuários às questões
- `LearningInsight`: Armazena insights gerados sobre o desempenho do usuário

#### Conteúdo Educacional
- `Subject`: Matérias principais (ex: Matemática, Física)
- `Topic`: Tópicos específicos dentro de uma matéria
- `Question`: Questões com diferentes níveis de dificuldade
- `Answer`: Opções de resposta para questões

#### Planejamento de Estudos
- `StudyPlan`: Planos de estudo criados pelo usuário ou pelo sistema
- `StudySession`: Sessões individuais de estudo dentro de um plano

#### Interação com IA
- `LLMInteraction`: Registra interações com modelos de linguagem
- `ExplanationCache`: Armazena explicações geradas para reutilização

## Fluxos de Trabalho Principais

### Fluxo de Aprendizado Adaptativo

1. O usuário seleciona um tópico para estudar
2. O sistema seleciona questões apropriadas ao nível de proficiência
3. O usuário responde às questões
4. O sistema avalia as respostas e atualiza o perfil de proficiência
5. O sistema fornece explicações personalizadas
6. Com base no desempenho, recomenda os próximos passos

### Fluxo de Criação de Plano de Estudos

1. O usuário solicita um plano de estudos (manual ou adaptativo)
2. O sistema analisa o histórico de desempenho (para planos adaptativos)
3. O sistema gera um plano com sessões programadas
4. O usuário completa as sessões conforme programado
5. O sistema rastreia o progresso e ajusta o plano conforme necessário

## APIs

O AprendaAI expõe as seguintes APIs para acesso a suas funcionalidades:

### Aprendizado Adaptativo
- `GET /api/adaptive-learning/topics`
- `GET /api/adaptive-learning/questions/{topicId}`
- `POST /api/adaptive-learning/submit-answer`
- `GET /api/adaptive-learning/explanation/{questionId}`
- `GET /api/adaptive-learning/performance-analysis`
- `GET /api/adaptive-learning/recommendations`

### Explicações
- `GET /api/explanations`
- `POST /api/explanations/generate`
- `GET /api/explanations/{id}`
- `POST /api/explanations/{id}/rate`
- `DELETE /api/explanations/{id}`

### Insights de Aprendizado
- `GET /api/insights`
- `POST /api/insights/generate`
- `GET /api/insights/{id}`
- `GET /api/insights/topic/{topicId}`

### Planos de Estudo
- `GET /api/study-plans`
- `POST /api/study-plans`
- `POST /api/study-plans/adaptive`
- `GET /api/study-plans/{id}`
- `PUT /api/study-plans/{id}`
- `DELETE /api/study-plans/{id}`
- `POST /api/study-plans/{planId}/sessions/{sessionId}/complete`
- `POST /api/study-plans/{planId}/sessions/{sessionId}/reschedule`

## Serviços Principais

### AdaptiveLearningService

Responsável pela lógica central de adaptação do conteúdo de aprendizado:

- `getNextQuestions()`: Obtém questões adequadas ao nível do usuário
- `generatePersonalizedExplanation()`: Cria explicações adaptadas
- `updateUserProgress()`: Atualiza o progresso com base nas respostas
- `getPerformanceAnalysis()`: Analisa o desempenho do usuário
- `getRecommendations()`: Sugere próximos passos para o usuário

### LLM Services

Serviços de integração com IA para personalização:

- `LLMServiceInterface`: Interface para serviços de IA
- `GeminiService`: Implementação usando a API do Google Gemini

## Jobs em Background

O sistema utiliza jobs em background para processar tarefas assíncronas:

- `AnalyzeUserPerformanceJob`: Analisa o desempenho do usuário e gera insights
- `GenerateStudyPlanJob`: Cria planos de estudo personalizados

## Testes

O projeto inclui testes unitários e de integração para garantir o correto funcionamento:

- Testes unitários para serviços e modelos
- Testes de integração para fluxos completos
- Testes de API para endpoints REST

## Desenvolvimento Recente

Recentemente, várias correções foram implementadas:

1. Atualização do modelo `UserAnswer`:
   - Renomeação de campos para consistência (`selected_option_id` → `answer_id`)
   - Mudança de `answer_time` para `time_spent`

2. Melhorias no `AdaptiveLearningController`:
   - Correção dos parâmetros na submissão de respostas
   - Implementação de cache para explicações
   - Ajuste do formato de retorno de dados

3. Aprimoramentos no `AdaptiveLearningService`:
   - Correção dos nomes de colunas (`difficulty` → `difficulty_level`)
   - Adição de logging para depuração
   - Melhoria no mapeamento de proficiência para dificuldade

4. Implementação do cache de explicações:
   - Nova tabela `explanation_cache`
   - Verificação de cache antes de gerar novas explicações

5. Geração de insights de aprendizado:
   - Registro de recomendações como insights
   - Processamento assíncrono via jobs

## Próximos Passos

Algumas áreas para desenvolvimento futuro incluem:

1. Implementação de algoritmos mais avançados de adaptabilidade
2. Melhorias na UI/UX do frontend
3. Integração com mais fontes de conteúdo educacional
4. Expansão dos relatórios analíticos para professores
5. Otimização de performance para escalabilidade

---

## Como Executar o Projeto

### Requisitos

- PHP 8.4+
- Composer
- Node.js e npm/yarn
- MySQL ou PostgreSQL

### Instalação

```bash
# Clonar o repositório
git clone [url-do-repositório]
cd aprendaAI

# Instalar dependências PHP
composer install

# Instalar dependências JavaScript
npm install

# Configurar variáveis de ambiente
cp .env.example .env
php artisan key:generate

# Migrar banco de dados
php artisan migrate

# Seed do banco de dados (opcional)
php artisan db:seed

# Iniciar servidor
php artisan serve
```

### Executando Testes

```bash
# Executar todos os testes
php artisan test

# Executar testes específicos
php artisan test --filter=AdaptiveLearningFlowTest
```
