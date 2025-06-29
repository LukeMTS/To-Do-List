import { defineStore } from 'pinia'
import { taskService } from '../services/taskService.js'

export const useTaskStore = defineStore('tasks', {
  state: () => ({
    tasks: [],
    loading: false,
    error: null
  }),

  getters: {
    // Tarefas não finalizadas
    pendingTasks: (state) => state.tasks.filter(task => !task.finalizado),
    
    // Tarefas finalizadas
    completedTasks: (state) => state.tasks.filter(task => task.finalizado),
    
    // Total de tarefas
    totalTasks: (state) => state.tasks.length
  },

  actions: {
    // Buscar todas as tarefas
    async fetchTasks() {
      this.loading = true
      this.error = null
      try {
        const response = await taskService.fetchTasks()
        this.tasks = response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao carregar tarefas'
        console.error('Erro ao buscar tarefas:', error)
      } finally {
        this.loading = false
      }
    },

    // Criar nova tarefa
    async createTask(task) {
      console.log('TaskStore - createTask chamado com dados:', task)
      this.loading = true
      this.error = null
      try {
        const response = await taskService.createTask(task)
        console.log('TaskStore - Resposta da API:', response)
        console.log('TaskStore - Tarefas antes de adicionar:', this.tasks.length)
        this.tasks.unshift(response.data) // Adiciona no início da lista
        console.log('TaskStore - Tarefas após adicionar:', this.tasks.length)
        console.log('TaskStore - Nova tarefa adicionada:', response.data)
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao criar tarefa'
        console.error('Erro ao criar tarefa:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Atualizar tarefa
    async updateTask(id, task) {
      this.loading = true
      this.error = null
      try {
        const response = await taskService.updateTask(id, task)
        const index = this.tasks.findIndex(t => t.id === id)
        if (index !== -1) {
          this.tasks[index] = response.data
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao atualizar tarefa'
        console.error('Erro ao atualizar tarefa:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Excluir tarefa
    async deleteTask(id) {
      this.loading = true
      this.error = null
      try {
        await taskService.deleteTask(id)
        this.tasks = this.tasks.filter(task => task.id !== id)
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao excluir tarefa'
        console.error('Erro ao excluir tarefa:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Toggle status da tarefa
    async toggleTask(id) {
      this.loading = true
      this.error = null
      try {
        const response = await taskService.toggleTask(id)
        const index = this.tasks.findIndex(t => t.id === id)
        if (index !== -1) {
          this.tasks[index] = response.data
        }
        return response.data
      } catch (error) {
        this.error = error.response?.data?.message || 'Erro ao alterar status da tarefa'
        console.error('Erro ao alterar status da tarefa:', error)
        throw error
      } finally {
        this.loading = false
      }
    },

    // Limpar erro
    clearError() {
      this.error = null
    }
  }
}) 