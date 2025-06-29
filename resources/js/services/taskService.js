import axios from 'axios'

// Configuração base do axios
const api = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

// Interceptor para tratamento de erros
api.interceptors.response.use(
    response => response,
    error => {
        console.error('API Error:', error.response?.data || error.message)
        return Promise.reject(error)
    }
)

export const taskService = {
    // Buscar todas as tarefas
    async fetchTasks() {
        const response = await api.get('/tasks')
        return response.data
    },

    // Buscar tarefa por ID
    async fetchTask(id) {
        const response = await api.get(`/tasks/${id}`)
        return response.data
    },

    // Criar nova tarefa
    async createTask(taskData) {
        const response = await api.post('/tasks', taskData)
        return response.data
    },

    // Atualizar tarefa
    async updateTask(id, taskData) {
        const response = await api.patch(`/tasks/${id}`, taskData)
        return response.data
    },

    // Excluir tarefa
    async deleteTask(id) {
        const response = await api.delete(`/tasks/${id}`)
        return response.data
    },

    // Toggle status da tarefa
    async toggleTask(id) {
        const response = await api.patch(`/tasks/${id}/toggle`)
        return response.data
    }
} 