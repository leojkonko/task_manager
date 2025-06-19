# Sistema de Gerenciamento de Tarefas

Um sistema completo de gerenciamento de tarefas desenvolvido com PHP, MySQL e Laminas Framework (antigo Zend Framework), seguindo as melhores práticas de desenvolvimento e padrões MVC.

## 🚀 Funcionalidades

### ✅ Gerenciamento de Tarefas
- Criar, editar, visualizar e excluir tarefas
- Status das tarefas: Pendente, Em Andamento, Concluída, Cancelada
- Níveis de prioridade: Baixa, Média, Alta, Urgente
- Data de vencimento com alertas para tarefas atrasadas
- Descrições detalhadas para cada tarefa

### 🏷️ Gerenciamento de Categorias
- Criar categorias personalizadas com cores
- Organizar tarefas por categoria
- Interface visual intuitiva com preview em tempo real

### 📊 Dashboard e Estatísticas
- Contadores visuais por status
- Lista organizada com filtros
- Destaque para tarefas atrasadas
- Interface responsiva e moderna

## 🛠️ Tecnologias Utilizadas

- **Backend**: PHP 8.0+
- **Framework**: Laminas MVC Framework
- **Banco de Dados**: MySQL 5.7+
- **Frontend**: Bootstrap 4, Font Awesome, jQuery
- **Gerenciador de Dependências**: Composer

## 📋 Pré-requisitos

- PHP 8.0 ou superior
- MySQL 5.7 ou MariaDB 10.2+
- Composer
- Servidor web (Apache/Nginx) ou PHP built-in server

## 🔧 Instalação

### 1. Clone o repositório
```bash
git clone [URL_DO_REPOSITORIO]
cd task-manager
```

### 2. Instale as dependências
```bash
composer install
```

### 3. Configure o banco de dados
1. Crie um banco de dados MySQL
2. Execute o script SQL localizado em `database/setup.sql`:
```bash
mysql -u root -p < database/setup.sql
```

### 4. Configure a conexão com o banco
Edite o arquivo `config/autoload/database.local.php` com suas credenciais:
```php
'username' => 'seu_usuario',
'password' => 'sua_senha',
```

### 5. Execute o servidor
```bash
# Usando o servidor built-in do PHP
php -S localhost:8080 -t public

# Ou configure um virtual host no Apache/Nginx
```

### 6. Acesse o sistema
Abra seu navegador e acesse: `http://localhost:8080`

## 📁 Estrutura do Projeto

```
task-manager/
├── config/                 # Configurações da aplicação
│   ├── autoload/           # Configurações automáticas
│   └── modules.config.php  # Módulos habilitados
├── database/               # Scripts do banco de dados
│   ├── setup.sql          # Estrutura e dados iniciais
│   └── README.md          # Documentação do banco
├── module/
│   ├── Application/        # Módulo principal
│   └── TaskManager/        # Módulo de gerenciamento de tarefas
│       ├── config/         # Configurações do módulo
│       ├── src/
│       │   ├── Controller/ # Controladores
│       │   ├── Model/      # Modelos e Table Gateways
│       │   └── Form/       # Formulários
│       └── view/           # Templates/Views
├── public/                 # Arquivos públicos
│   ├── css/               # Estilos
│   ├── js/                # Scripts
│   └── index.php          # Ponto de entrada
└── vendor/                # Dependências do Composer
```

## 🎯 Como Usar

### Gerenciando Tarefas
1. **Criar Nova Tarefa**: Clique em "Nova Tarefa" na barra de navegação
2. **Visualizar Tarefas**: A página inicial mostra todas as tarefas com estatísticas
3. **Editar Tarefa**: Clique no ícone de edição na lista de tarefas
4. **Marcar como Concluída**: Use o botão de check para alternar o status
5. **Excluir Tarefa**: Clique no ícone da lixeira (com confirmação)

### Gerenciando Categorias
1. **Criar Categoria**: Acesse "Categorias" > "Nova Categoria"
2. **Escolher Cor**: Use o seletor de cor para facilitar identificação
3. **Visualizar Preview**: Veja como a categoria aparecerá em tempo real
4. **Organizar**: Associe tarefas às categorias durante a criação/edição

## 🔐 Dados de Teste

O sistema inclui dados de exemplo:

### Usuários
- **Admin**: admin@taskmanager.com (senha: password)
- **João**: john@taskmanager.com (senha: password)

### Categorias Pré-definidas
- Trabalho (Azul)
- Pessoal (Verde)
- Urgente (Vermelho)
- Estudos (Roxo)

### Tarefas de Exemplo
- Configurar ambiente de desenvolvimento
- Criar estrutura do banco de dados
- Implementar autenticação de usuários
- Estudar padrões de design

## 🎨 Recursos da Interface

- **Design Responsivo**: Funciona em desktop, tablet e mobile
- **Tema Moderno**: Interface limpa e profissional
- **Feedback Visual**: Mensagens de sucesso/erro
- **Navegação Intuitiva**: Menu organizado e acessível
- **Indicadores Visuais**: Cores para prioridades e status
- **Alertas**: Destaque para tarefas atrasadas

## 🔄 Commits Organizados

O desenvolvimento seguiu small commits organizados:

1. ✅ **Initial project setup** - Estrutura inicial Laminas MVC
2. ✅ **Database configuration** - Configuração MySQL e scripts
3. ✅ **TaskManager module** - Modelos e Table Gateways
4. ✅ **Controllers and forms** - Controladores e formulários
5. ✅ **Comprehensive views** - Templates completos
6. ✅ **UI improvements** - Layout, CSS e mensagens flash

## 🤝 Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -m 'Add nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## 📝 Próximas Funcionalidades

- [ ] Sistema de autenticação completo
- [ ] Upload de anexos nas tarefas
- [ ] Sistema de comentários
- [ ] Filtros avançados e busca
- [ ] Relatórios e dashboards
- [ ] API REST
- [ ] Notificações por email
- [ ] Temas customizáveis

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE.md](LICENSE.md) para detalhes.

## 👨‍💻 Desenvolvedor

Desenvolvido com ❤️ seguindo as melhores práticas de desenvolvimento PHP e padrões do Laminas Framework.

---

**Nota**: Este sistema foi desenvolvido como demonstração de competências em PHP, MySQL e Laminas Framework, seguindo padrões profissionais de desenvolvimento.
