# API de Tarefas - Documentação para Postman

## Base URL
```
http://localhost:8080
```

## Endpoints Disponíveis

### 1. Listar Tarefas
- **URL**: `GET /api/tasks/list`
- **Parâmetros de Query** (opcionais):
  - `page`: Número da página (padrão: 1)
  - `limit`: Itens por página (padrão: 10)
  - `status`: Filtrar por status (`pending`, `in_progress`, `completed`, `cancelled`)
  - `priority`: Filtrar por prioridade (`low`, `medium`, `high`, `urgent`)
  - `search`: Buscar no título

**Exemplo**:
```
GET http://localhost:8080/api/tasks/list?page=1&limit=5&status=pending
```

### 2. Obter Tarefa Específica
- **URL**: `GET /api/tasks/{id}`

**Exemplo**:
```
GET http://localhost:8080/api/tasks/1
```

### 3. Criar Nova Tarefa
- **URL**: `POST /api/tasks/create`
- **Content-Type**: `application/json` ou `application/x-www-form-urlencoded`

**Body JSON (recomendado)**:
```json
{
    "title": "Minha nova tarefa",
    "description": "Descrição detalhada da tarefa",
    "status": "pending",
    "priority": "medium",
    "due_date": "2025-06-30T14:30:00"
}
```

**Body Form-data** (alternativo):
- `title`: Minha nova tarefa
- `description`: Descrição detalhada da tarefa
- `status`: pending
- `priority`: medium
- `due_date`: 2025-06-30T14:30:00

### 4. Atualizar Tarefa
- **URL**: `PUT /api/tasks/update/{id}` ou `POST /api/tasks/update/{id}`
- **Content-Type**: `application/json` ou `application/x-www-form-urlencoded`

**Exemplo**:
```
PUT http://localhost:8080/api/tasks/update/1
```

**Body JSON**:
```json
{
    "title": "Título atualizado",
    "description": "Nova descrição",
    "status": "in_progress",
    "priority": "high"
}
```

### 5. Excluir Tarefa
- **URL**: `DELETE /api/tasks/delete/{id}` ou `POST /api/tasks/delete/{id}`

**Exemplo**:
```
DELETE http://localhost:8080/api/tasks/delete/1
```

### 6. Marcar Tarefa como Concluída
- **URL**: `POST /api/tasks/complete/{id}`

**Exemplo**:
```
POST http://localhost:8080/api/tasks/complete/1
```

### 7. Marcar Tarefa como Em Andamento
- **URL**: `POST /api/tasks/start/{id}`

**Exemplo**:
```
POST http://localhost:8080/api/tasks/start/1
```

### 8. Duplicar Tarefa
- **URL**: `POST /api/tasks/duplicate/{id}`

**Exemplo**:
```
POST http://localhost:8080/api/tasks/duplicate/1
```

### 9. Obter Estatísticas
- **URL**: `GET /api/tasks/statistics`

**Exemplo**:
```
GET http://localhost:8080/api/tasks/statistics
```

## Validações Aplicadas

### Regras de Negócio
- ✅ **Criação apenas em dias úteis**: Tarefas só podem ser criadas de segunda a sexta-feira
- ✅ **Atualização apenas com status "pending"**: Tarefas só podem ser atualizadas se estiverem com status "pending"
- ✅ **Exclusão apenas com status "pending"**: Tarefas só podem ser excluídas se estiverem com status "pending"
- ✅ **Exclusão apenas após 5 dias**: Tarefas só podem ser excluídas se foram criadas há mais de 5 dias
- ✅ **Data de vencimento**: Não pode ser no passado (apenas na criação)

### Título
- ✅ **Obrigatório** (apenas na criação)
- ✅ **Mínimo**: 3 caracteres
- ✅ **Máximo**: 200 caracteres
- ✅ **Caracteres permitidos**: letras, números, espaços, pontuação básica (. , ! ? - _)

### Descrição
- ✅ **Opcional**
- ✅ **Máximo**: 1000 caracteres

### Status
- ✅ **Valores válidos**: `pending`, `in_progress`, `completed`, `cancelled`

### Prioridade
- ✅ **Valores válidos**: `low`, `medium`, `high`, `urgent`

### Data de Vencimento
- ✅ **Opcional**
- ✅ **Formato**: `Y-m-d H:i:s` ou `Y-m-d\TH:i`
- ✅ **Não pode ser no passado** (apenas na criação)

## Exemplos de Testes de Validação

### 1. Teste de Título Vazio
```json
POST /api/tasks/create
{
    "title": "",
    "description": "Teste"
}
```
**Resposta esperada**: Erro de validação

### 2. Teste de Título Muito Curto
```json
POST /api/tasks/create
{
    "title": "AB",
    "description": "Teste"
}
```
**Resposta esperada**: Erro de validação

### 3. Teste de Título Muito Longo
```json
POST /api/tasks/create
{
    "title": "A".repeat(201),
    "description": "Teste"
}
```
**Resposta esperada**: Erro de validação

### 4. Teste de Status Inválido
```json
POST /api/tasks/create
{
    "title": "Tarefa válida",
    "status": "invalid_status"
}
```
**Resposta esperada**: Erro de validação

### 5. Teste de Data no Passado
```json
POST /api/tasks/create
{
    "title": "Tarefa válida",
    "due_date": "2020-01-01T10:00:00"
}
```
**Resposta esperada**: Erro de validação

### 6. Teste de Criação em Fim de Semana
```json
POST /api/tasks/create
{
    "title": "Tarefa de fim de semana",
    "description": "Tentativa de criar tarefa no sábado ou domingo"
}
```
**Resposta esperada**: Erro de validação (apenas na execução durante fim de semana)

### 7. Teste de Atualização de Tarefa com Status Não-Pending
```json
PUT /api/tasks/update/1
{
    "title": "Tentativa de atualizar tarefa concluída",
    "description": "Esta tarefa já está completed/in_progress/cancelled"
}
```
**Resposta esperada**: Erro 403 - Operação não permitida

### 8. Teste de Exclusão de Tarefa com Status Não-Pending
```json
DELETE /api/tasks/delete/1
```
**Resposta esperada**: Erro 403 - Operação não permitida (se a tarefa não estiver com status "pending")

### 9. Teste de Exclusão de Tarefa Muito Recente (menos de 5 dias)
```json
DELETE /api/tasks/delete/1
```
**Resposta esperada**: Erro 403 - Operação não permitida
```json
{
    "success": false,
    "message": "Operação não permitida",
    "errors": {
        "operation": ["Tarefas só podem ser excluídas após 5 dias da criação. Aguarde mais 3 dia(s)"]
    },
    "error_code": "OPERATION_NOT_ALLOWED"
}
```

## Respostas de Sucesso

### Criação/Atualização Bem-sucedida
```json
{
    "success": true,
    "message": "Tarefa criada com sucesso",
    "data": {
        "id": 1,
        "title": "Minha nova tarefa",
        "description": "Descrição detalhada",
        "status": "pending",
        "priority": "medium",
        "due_date": "2025-06-30 14:30:00",
        "user_id": 1,
        "created_at": "2025-06-19 10:30:00",
        "updated_at": "2025-06-19 10:30:00"
    }
}
```

### Listagem de Tarefas
```json
{
    "success": true,
    "data": {
        "tasks": [
            {
                "id": 1,
                "title": "Tarefa exemplo",
                "description": "Descrição da tarefa",
                "status": "pending",
                "priority": "medium",
                "due_date": "2025-06-30 14:30:00",
                "user_id": 1,
                "created_at": "2025-06-19 10:30:00",
                "updated_at": "2025-06-19 10:30:00"
            }
        ],
        "pagination": {
            "total": 15,
            "page": 1,
            "limit": 10,
            "total_pages": 2
        }
    }
}
```

### Estatísticas
```json
{
    "success": true,
    "data": {
        "total_tasks": 25,
        "pending_tasks": 8,
        "in_progress_tasks": 3,
        "completed_tasks": 12,
        "cancelled_tasks": 2,
        "overdue_tasks": [
            {
                "id": 5,
                "title": "Tarefa atrasada",
                "due_date": "2025-06-15 10:00:00"
            }
        ]
    }
}
```

## Respostas de Erro

### Erro de Validação
```json
{
    "success": false,
    "message": "Dados de entrada inválidos",
    "errors": {
        "title": ["O título deve ter pelo menos 3 caracteres"],
        "status": ["Status inválido. Valores aceitos: pending, in_progress, completed, cancelled"]
    },
    "error_code": "VALIDATION_ERROR"
}
```

### Tarefa Não Encontrada
```json
{
    "success": false,
    "message": "Tarefa não encontrada",
    "error_code": "TASK_NOT_FOUND"
}
```

### Método HTTP Inválido
```json
{
    "success": false,
    "message": "Método POST é obrigatório",
    "error_code": "INVALID_METHOD"
}
```

### JSON Inválido
```json
{
    "success": false,
    "message": "JSON inválido: Syntax error",
    "error_code": "INVALID_JSON"
}
```

### Operação Não Permitida (Status Incorreto)
```json
{
    "success": false,
    "message": "Operação não permitida",
    "errors": {
        "operation": ["Apenas tarefas com status \"pending\" podem ser atualizadas"]
    },
    "error_code": "OPERATION_NOT_ALLOWED"
}
```

## Códigos de Status HTTP

| Código | Significado | Quando é usado |
|--------|-------------|----------------|
| 200 | OK | Operação realizada com sucesso |
| 201 | Created | Recurso criado com sucesso |
| 400 | Bad Request | Dados inválidos ou malformados |
| 403 | Forbidden | Operação não permitida (ex: atualizar tarefa não-pending) |
| 404 | Not Found | Recurso não encontrado |
| 405 | Method Not Allowed | Método HTTP não permitido |
| 500 | Internal Server Error | Erro interno do servidor |

## Testando com Collection do Postman

Você pode importar esta collection no Postman para testar todos os endpoints:

```json
{
    "info": {
        "name": "Task Manager API",
        "description": "Collection para testar a API de gerenciamento de tarefas"
    },
    "item": [
        {
            "name": "Listar Tarefas",
            "request": {
                "method": "GET",
                "url": "{{baseUrl}}/api/tasks/list?page=1&limit=10"
            }
        },
        {
            "name": "Criar Tarefa",
            "request": {
                "method": "POST",
                "url": "{{baseUrl}}/api/tasks/create",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"title\": \"Nova tarefa via API\",\n    \"description\": \"Criada através do Postman\",\n    \"priority\": \"medium\",\n    \"status\": \"pending\"\n}"
                }
            }
        },
        {
            "name": "Obter Tarefa",
            "request": {
                "method": "GET",
                "url": "{{baseUrl}}/api/tasks/1"
            }
        },
        {
            "name": "Atualizar Tarefa",
            "request": {
                "method": "PUT",
                "url": "{{baseUrl}}/api/tasks/update/1",
                "header": [
                    {
                        "key": "Content-Type",
                        "value": "application/json"
                    }
                ],
                "body": {
                    "mode": "raw",
                    "raw": "{\n    \"title\": \"Tarefa atualizada\",\n    \"status\": \"in_progress\"\n}"
                }
            }
        },
        {
            "name": "Marcar como Concluída",
            "request": {
                "method": "POST",
                "url": "{{baseUrl}}/api/tasks/complete/1"
            }
        },
        {
            "name": "Duplicar Tarefa",
            "request": {
                "method": "POST",
                "url": "{{baseUrl}}/api/tasks/duplicate/1"
            }
        },
        {
            "name": "Excluir Tarefa",
            "request": {
                "method": "DELETE",
                "url": "{{baseUrl}}/api/tasks/delete/1"
            }
        },
        {
            "name": "Estatísticas",
            "request": {
                "method": "GET",
                "url": "{{baseUrl}}/api/tasks/statistics"
            }
        }
    ],
    "variable": [
        {
            "key": "baseUrl",
            "value": "http://localhost:8080"
        }
    ]
}
```