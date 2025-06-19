# Configuração do Banco de Dados

## Pré-requisitos

- MySQL 5.7+ ou MariaDB 10.2+
- PHP 8.0+
- Extensão PDO MySQL habilitada

## Configuração

### 1. Criar o banco de dados

Execute o script SQL localizado em `database/setup.sql`:

```bash
mysql -u root -p < database/setup.sql
```

### 2. Configurar conexão

O arquivo de configuração já está criado em `config/autoload/database.local.php`.

Edite as credenciais conforme necessário:

```php
'username' => 'seu_usuario',
'password' => 'sua_senha',
```

## Estrutura do Banco

### Tabelas

- **users**: Gerencia usuários do sistema
- **task_categories**: Categorias para organizar tarefas
- **tasks**: Tarefas principais do sistema
- **task_attachments**: Anexos das tarefas
- **task_comments**: Comentários nas tarefas

### Dados de Teste

O script inclui dados de exemplo:
- Usuário admin (admin@taskmanager.com)
- Usuário john_doe (john@taskmanager.com)
- Senha padrão para ambos: "password"
- Categorias pré-definidas
- Tarefas de exemplo

## Comandos Úteis

### Backup
```bash
mysqldump -u root -p task_manager > backup.sql
```

### Restaurar
```bash
mysql -u root -p task_manager < backup.sql
```