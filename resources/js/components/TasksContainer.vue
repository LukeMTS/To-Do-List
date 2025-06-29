<template>
  <div class="content-tasks">
    <div class="header">
      <h2>Tarefas</h2>
      <div class="button rounded" @click="openModal()">
        <div class="icon w-embed">
          <svg width="24" height="24" viewbox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
          </svg>
        </div>
      </div>
    </div>
    
    <TaskList />
    
    <div class="footer">
      <div>Alloy</div>
    </div>
    
    <TaskModal 
      :isOpen="isModalOpen"
      :editingTask="editingTask"
      @close="closeModal"
    />
  </div>
</template>

<script>
import { ref } from 'vue'
import TaskList from './TaskList.vue'
import TaskModal from './TaskModal.vue'

export default {
  name: 'TasksContainer',
  components: {
    TaskList,
    TaskModal
  },
  setup() {
    const isModalOpen = ref(false)
    const editingTask = ref(null)

    const openModal = (task = null) => {
      console.log('Botão + clicado! Abrindo modal...', { task, isModalOpen: isModalOpen.value })
      editingTask.value = task
      isModalOpen.value = true
      console.log('Modal aberta:', { isModalOpen: isModalOpen.value, editingTask: editingTask.value })
    }

    const closeModal = () => {
      isModalOpen.value = false
      editingTask.value = null
    }

    return {
      isModalOpen,
      editingTask,
      openModal,
      closeModal
    }
  }
}
</script> 