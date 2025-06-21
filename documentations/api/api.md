# API Documentation - Sistema de Gerenciamento de Tarefas

## 📋 Visão Geral

Esta API permite o gerenciamento completo de tarefas através de endpoints RESTful. Todos os endpoints suportam requisições AJAX retornando JSON quando o header `X-Requested-With: XMLHttpRequest` é enviado.

**Base URL**: `http://localhost:8080`

## 🔐 Autenticação

> **Nota**: Atualmente o sistema utiliza um usuário fixo (ID: 1) para demonstração. Em produção, seria implementado um sistema de autenticação JWT ou sessões.

## 📝 Formato de Resposta

### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Operação realizada com sucesso",
    "data": { /* dados da resposta */ }
}
```

### Resposta de Erro
```json
{
    "success": false,
    "message": "Descrição do erro"
}
```

---

## 🎯 Endpoints Disponíveis

### 1. **Listar Tarefas**

**GET** `/tasks`

Lista todas as tarefas do usuário com suporte a filtros e paginação.

#### Parâmetros de Query (opcionais)

| Parâmetro | Tipo | Descrição | Valores Possíveis |
|-----------|------|-----------|-------------------|
| `page` | integer | Número da página | Padrão: 1 |
| `limit` | integer | Itens por página | Padrão: 10 |
| `status` | string | Filtrar por status | `pending`, `in_progress`, `completed`, `cancelled` |
| `priority` | string | Filtrar por prioridade | `low`, `medium`, `high`, `urgent` |
| `category_id` | integer | ID da categoria | |
| `search` | string | Buscar no título | |
| `order_by` | string | Campo para ordenação | `created_at`, `due_date`, `priority`, `title` |
| `order_direction` | string | Direção da ordenação | `ASC`, `DESC` |

#### Exemplo de Requisição
```bash
GET /tasks?page=1&limit=5&status=pending&priority=high&order_by=due_date&order_direction=ASC
```

#### Resposta de Sucesso (JSON)
```json
{
    "success": true,
    "data": {
        "tasks": [
            {
                "id": 1,
                "title": "Configurar ambiente de desenvolvimento",
                "description": "Configurar PHP, MySQL e Laminas Framework",
                "status": "completed",
                "priority": "high",
                "due_date": "2025-06-15 17:00:00",
                "completed_at": "2025-06-14 16:30:00",
                "user_id": 1,
                "category_id": 1,
                "created_at": "2025-06-10 09:00:00",
                "updated_at": "2025-06-14 16:30:00",
                "is_completed": true,
                "is_in_progress": false,
                "is_pending": false,
                "is_overdue": false
            }
        ],
        "pagination": {
            "total": 25,
            "page": 1,
            "limit": 5,
            "total_pages": 5
        }
    }
}
```

---

### 2. **Visualizar Tarefa Específica**

**GET** `/tasks/view/{id}`

Retorna os detalhes de uma tarefa específica.

#### Parâmetros da URL

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da tarefa |

#### Exemplo de Requisição
```bash
GET /tasks/view/1
```

#### Resposta de Sucesso
```json
{
    "success": true,
    "data": {
        "id": 1,
        "title": "Configurar ambiente de desenvolvimento",
        "description": "Configurar PHP, MySQL e Laminas Framework",
        "status": "completed",
        "priority": "high",
        "due_date": "2025-06-15 17:00:00",
        "completed_at": "2025-06-14 16:30:00",
        "user_id": 1,
        "category_id": 1,
        "created_at": "2025-06-10 09:00:00",
        "updated_at": "2025-06-14 16:30:00",
        "is_completed": true,
        "is_in_progress": false,
        "is_pending": false,
        "is_overdue": false
    }
}
```

#### Resposta de Erro
```json
{
    "success": false,
    "message": "Tarefa não encontrada"
}
```

---

### 3. **Criar Nova Tarefa**

**POST** `/tasks/create`

Cria uma nova tarefa no sistema.

#### Corpo da Requisição
```json
{
    "title": "Nova Tarefa",
    "description": "Descrição detalhada da tarefa",
    "status": "pending",
    "priority": "medium",
    "category_id": 1,
    "due_date": "2025-07-01 12:00:00"
}
```

#### Campos Obrigatórios

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `title` | string | Título da tarefa (obrigatório) |

#### Campos Opcionais

| Campo | Tipo | Descrição | Valores Possíveis |
|-------|------|-----------|-------------------|
| `description` | string | Descrição da tarefa | |
| `status` | string | Status inicial | `pending` (padrão), `in_progress`, `completed`, `cancelled` |
| `priority` | string | Prioridade | `low`, `medium` (padrão), `high`, `urgent` |
| `category_id` | integer | ID da categoria | |
| `due_date` | string | Data de vencimento | Formato: YYYY-MM-DD HH:mm:ss |

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Tarefa criada com sucesso",
    "data": {
        "id": 5,
        "title": "Nova Tarefa",
        "description": "Descrição detalhada da tarefa",
        "status": "pending",
        "priority": "medium",
        "due_date": "2025-07-01 12:00:00",
        "completed_at": null,
        "user_id": 1,
        "category_id": 1,
        "created_at": "2025-06-19 10:30:00",
        "updated_at": "2025-06-19 10:30:00",
        "is_completed": false,
        "is_in_progress": false,
        "is_pending": true,
        "is_overdue": false
    }
}
```

#### Resposta de Erro
```json
{
    "success": false,
    "message": "O título da tarefa é obrigatório"
}
```

---

### 4. **Editar Tarefa**

**POST** `/tasks/edit/{id}`

Atualiza uma tarefa existente.

#### Parâmetros da URL

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da tarefa a ser editada |

#### Corpo da Requisição
```json
{
    "title": "Título Atualizado",
    "description": "Nova descrição",
    "status": "in_progress",
    "priority": "high",
    "due_date": "2025-07-15 14:00:00"
}
```

> **Nota**: Todos os campos são opcionais. Apenas os campos enviados serão atualizados.

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Tarefa atualizada com sucesso",
    "data": {
        /* dados da tarefa atualizada */
    }
}
```

---

### 5. **Excluir Tarefa**

**POST** `/tasks/delete/{id}`

Remove uma tarefa do sistema.

#### Parâmetros da URL

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da tarefa a ser excluída |

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Tarefa excluída com sucesso"
}
```

#### Resposta de Erro
```json
{
    "success": false,
    "message": "Tarefa não encontrada"
}
```

---

### 6. **Marcar Tarefa como Concluída**

**POST** `/tasks/complete/{id}`

Marca uma tarefa como concluída e define automaticamente a data de conclusão.

#### Parâmetros da URL

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da tarefa |

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Tarefa marcada como concluída",
    "data": {
        "id": 1,
        "status": "completed",
        "completed_at": "2025-06-19 15:30:00",
        /* outros campos da tarefa */
    }
}
```

---

### 7. **Marcar Tarefa como Em Andamento**

**POST** `/tasks/start/{id}`

Altera o status da tarefa para "em andamento".

#### Parâmetros da URL

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da tarefa |

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Tarefa marcada como em andamento",
    "data": {
        "id": 1,
        "status": "in_progress",
        /* outros campos da tarefa */
    }
}
```

---

### 8. **Duplicar Tarefa**

**POST** `/tasks/duplicate/{id}`

Cria uma cópia da tarefa com status "pending" e título modificado.

#### Parâmetros da URL

| Parâmetro | Tipo | Descrição |
|-----------|------|-----------|
| `id` | integer | ID da tarefa a ser duplicada |

#### Resposta de Sucesso
```json
{
    "success": true,
    "message": "Tarefa duplicada com sucesso",
    "data": {
        "id": 6,
        "title": "Configurar ambiente de desenvolvimento (Cópia)",
        "status": "pending",
        "completed_at": null,
        /* outros campos copiados da tarefa original */
    }
}
```

---

### 9. **Estatísticas das Tarefas**

**GET** `/tasks/statistics`

Retorna estatísticas e métricas das tarefas do usuário.

#### Resposta de Sucesso
```json
{
    "success": true,
    "data": {
        "total": 10,
        "by_status": {
            "pending": 3,
            "in_progress": 2,
            "completed": 4,
            "cancelled": 1
        },
        "overdue": 2,
        "overdue_tasks": [
            {
                "id": 3,
                "title": "Tarefa Atrasada",
                "due_date": "2025-06-15 12:00:00",
                "is_overdue": true,
                /* outros campos */
            }
        ]
    }
}
```

---

## 📊 Modelos de Dados

### Objeto Task

```json
{
    "id": 1,
    "title": "string",
    "description": "string|null",
    "status": "pending|in_progress|completed|cancelled",
    "priority": "low|medium|high|urgent",
    "due_date": "YYYY-MM-DD HH:mm:ss|null",
    "completed_at": "YYYY-MM-DD HH:mm:ss|null",
    "user_id": 1,
    "category_id": "integer|null",
    "created_at": "YYYY-MM-DD HH:mm:ss",
    "updated_at": "YYYY-MM-DD HH:mm:ss",
    "is_completed": "boolean",
    "is_in_progress": "boolean",
    "is_pending": "boolean",
    "is_overdue": "boolean"
}
```

### Status Disponíveis

| Valor | Descrição |
|-------|-----------|
| `pending` | Pendente (padrão) |
| `in_progress` | Em Andamento |
| `completed` | Concluída |
| `cancelled` | Cancelada |

### Prioridades Disponíveis

| Valor | Descrição |
|-------|-----------|
| `low` | Baixa |
| `medium` | Média (padrão) |
| `high` | Alta |
| `urgent` | Urgente |

---

## 🔧 Códigos de Erro HTTP

| Código | Descrição |
|--------|-----------|
| `200` | Sucesso |
| `400` | Dados inválidos |
| `404` | Recurso não encontrado |
| `500` | Erro interno do servidor |

---

## 💡 Exemplos de Uso

### Criar uma Tarefa Urgente
```bash
curl -X POST http://localhost:8080/tasks/create \
  -H "Content-Type: application/json" \
  -H "X-Requested-With: XMLHttpRequest" \
  -d '{
    "title": "Corrigir bug crítico",
    "description": "Bug afetando usuários em produção",
    "priority": "urgent",
    "status": "in_progress",
    "due_date": "2025-06-20 18:00:00"
  }'
```

### Listar Tarefas Pendentes Ordenadas por Prioridade
```bash
curl "http://localhost:8080/tasks?status=pending&order_by=priority&order_direction=DESC" \
  -H "X-Requested-With: XMLHttpRequest"
```

### Marcar Tarefa como Concluída
```bash
curl -X POST http://localhost:8080/tasks/complete/1 \
  -H "X-Requested-With: XMLHttpRequest"
```

---

## 📈 Funcionalidades Futuras

- [ ] Autenticação JWT
- [ ] Upload de anexos
- [ ] Sistema de comentários
- [ ] Webhooks para notificações
- [ ] API de relatórios
- [ ] Filtros avançados
- [ ] Bulk operations

---

## 🤝 Suporte

Para dúvidas ou sugestões sobre a API, consulte a documentação do projeto ou entre em contato com a equipe de desenvolvimento.

**Versão da API**: 1.0  
**Última atualização**: Junho 2025