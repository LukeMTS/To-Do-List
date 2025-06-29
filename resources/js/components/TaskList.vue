<template>
  <div class="tasks">
    <div class="form-fields no-space-top w-form">
      <form class="form">
        <div class="block-tasks">
          <div v-if="loading" class="loading">
            <div>Carregando tarefas...</div>
          </div>
          
          <div v-else-if="error" class="error-message">
            {{ error }}
          </div>
          
          <div v-else-if="tasks.length === 0" class="empty-state">
            <div>Nenhuma tarefa encontrada. Clique no botão + para criar uma nova tarefa.</div>
          </div>
          
          <TaskItem 
            v-else
            v-for="task in tasks" 
            :key="task.id" 
            :task="task"
          />
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { computed, onMounted } from 'vue'
import { useTaskStore } from '../stores/taskStore.js'
import TaskItem from './TaskItem.vue'

export default {
  name: 'TaskList',
  components: {
    TaskItem
  },
  setup() {
    const taskStore = useTaskStore()

    const tasks = computed(() => taskStore.tasks)
    const loading = computed(() => taskStore.loading)
    const error = computed(() => taskStore.error)

    onMounted(async () => {
      await taskStore.fetchTasks()
    })

    return {
      tasks,
      loading,
      error
    }
  }
}
</script>

<style scoped>
.loading, .empty-state {
  text-align: center;
  padding: 20px;
  color: #666;
}

.error-message {
  color: #ec3872;
  text-align: center;
  background-color: #ec38721a;
  border-radius: 4px;
  padding: 10px;
  margin: 10px 0;
}
</style> 