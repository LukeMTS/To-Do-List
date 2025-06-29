To-Do List
API e aplicação web para gerenciar uma lista de tarefas.

Está disponível uma collection do postman neste diretório.

Este projeto foi desenvolvido como teste técnico para uma vaga de desenvolvedor.

🚀 Tecnologias Utilizadas
Backend
Laravel 12.x (PHP 8.2+)

SQLite (banco de dados leve e local)

Frontend
Node v22.17.0

Vue.js 3.4

Pinia 2.1 (state management)

Vite 6.3 (build tool)

TailwindCSS 4.0 (estilização)


📦 Configuração do Ambiente

1️⃣ Clone o repositório
   git clone <seu-repo-url>
   cd <pasta-do-projeto>
2️⃣ Instale as dependências
   composer install
   npm install
3️⃣ Configure as variáveis de ambiente
   -bash
      cp .env.example .env
      php artisan key:generate

   -env
      DB_CONNECTION=sqlite
      DB_DATABASE=database/database.sqlite

      touch database/database.sqlite
4️⃣ Rode as migrações
   php artisan migrate
5️⃣ Compile os assets front-end
   npm run build

🏃‍♂️ Como Rodar
   -back
      php artisan serve
   -front
      npm run build

🔗 Endpoints da API
Base URL: {{host}} 

Método	   Rota	                     Descrição
GET	      /api/tasks	               Listar todas as tarefas
GET	      /api/tasks/{id}	         Detalhar uma tarefa específica
POST	      /api/tasks	               Criar nova tarefa
PUT	      /api/tasks/{id}	         Atualizar uma tarefa
DELETE	   /api/tasks/{id}	         Excluir (soft delete) uma tarefa
PATCH	      /api/tasks/{id}/toggle	   Alterar status (finalizada/não finalizada)

📑 Exemplo de Request Body
Criar/Atualizar tarefa:

{
  "nome": "Comprar mantimentos",
  "descricao": "Leite, pão, ovos e frutas",
  "finalizado": false,
  "data_limite": "2025-07-01"
}
