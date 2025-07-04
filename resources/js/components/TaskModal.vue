<template>
  <div class="modal-task" v-if="isOpen" @click="closeModal" :style="{ display: 'flex', opacity: 1 }">
    <div class="container-modal regular" @click.stop>
      <div class="top-modal">
        <h3>{{ editingTask ? 'Editar tarefa' : 'Nova tarefa' }}</h3>
        <div class="close-modal" @click="closeModal">
          <div class="icon w-embed">
            <svg width="24" height="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M17 7L7 17M7 7L17 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
            </svg>
          </div>
        </div>
      </div>
      
      <div class="content-modal">
        <TaskForm 
          ref="taskFormRef"
          :task="editingTask"
          @submit="handleFormSubmit"
        />
      </div>
      
      <div class="bottom-modal">
        <div class="flex-block-horizontal-right-align">
          <div class="button outlined rounded" @click="closeModal">
            <div>Fechar</div>
          </div>
          <button type="button" class="button rounded" @click="handleSave" :class="{ 'loading': loading }">
            <div>{{ loading ? 'Salvando...' : (editingTask ? 'Atualizar' : 'Salvar') }}</div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, watch } from 'vue'
import { useTaskStore } from '../stores/taskStore.js'
import TaskForm from './TaskForm.vue'

export default {
  name: 'TaskModal',
  components: {
    TaskForm
  },
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    editingTask: {
      type: Object,
      default: null
    }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const taskStore = useTaskStore()
    const formData = ref(null)
    const taskFormRef = ref(null)
    const loading = computed(() => taskStore.loading)

    // Limpar formData quando a modal é fechada ou quando editingTask muda
    watch(() => props.isOpen, (isOpen) => {
      if (!isOpen) {
        formData.value = null
      }
    })

    watch(() => props.editingTask, (newTask) => {
      // Limpar formData quando mudar de criação para edição ou vice-versa
      formData.value = null
    })

    const closeModal = () => {
      formData.value = null // Limpar dados ao fechar
      emit('close')
    }

    const handleSave = async () => {
      // Proteção contra múltiplas chamadas simultâneas
      if (loading.value) {
        return
      }
      
      // Se não temos formData, vamos tentar obter do formulário
      if (!formData.value && taskFormRef.value) {
        taskFormRef.value.handleSubmit()
        return
      }

      if (!formData.value) {
        return
      }

      try {
        // Verificar se é criação ou edição
        if (props.editingTask) {
          await taskStore.updateTask(props.editingTask.id, formData.value)
        } else {
          await taskStore.createTask(formData.value)
        }
        
        formData.value = null // Limpar dados após salvar com sucesso
        closeModal()
      } catch (error) {
        console.error('Erro ao salvar tarefa:', error)
        // Não limpar formData em caso de erro para permitir nova tentativa
      }
    }

    const handleFormSubmit = (data) => {
      // Proteção contra múltiplas chamadas
      if (loading.value) {
        return
      }
      
      formData.value = data
      handleSave()
    }

    return {
      loading,
      closeModal,
      handleSave,
      handleFormSubmit,
      taskFormRef
    }
  }
}
</script>

<style scoped>
.button.loading {
  opacity: 0.7;
  cursor: not-allowed;
}
</style> 