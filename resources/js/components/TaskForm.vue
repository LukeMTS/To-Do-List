<template>
  <div class="form-fields w-form w-full">
    <form @submit.prevent="handleSubmit">
      <div>
        <!-- Título -->
        <label for="task-name" class="text-sm text-gray-500">
          Título
        </label>
        <div class="input-wrap relative">
          <input id="task-name" type="text" maxlength="256" v-model="form.nome" required @focus="focus.nome = true"
            @blur="focus.nome = false"
            class="input w-input block"
            autocomplete="off" />
        </div>

        <!-- Detalhes -->
        <label for="task-descricao" class="text-sm text-gray-500">
          Detalhes
        </label>
        <div class="input-wrap relative">
          <input id="task-descricao" type="text" maxlength="256" v-model="form.descricao"
            @focus="focus.descricao = true" @blur="focus.descricao = false"
            class="input w-input block"
            autocomplete="off" />
        </div>

        <!-- Data -->
        <label for="task-data-limite" class="text-sm text-gray-500">
          Data
        </label>
        <div class="input-wrap relative">
          <input id="task-data-limite" type="date" maxlength="256" v-model="form.data_limite"
            @focus="focus.data_limite = true" @blur="focus.data_limite = false"
            class="input"
            autocomplete="off" />
        </div>
      </div>
    </form>
  </div>
</template>

<script>
import { ref, watch } from 'vue'

export default {
  name: 'TaskForm',
  props: {
    task: {
      type: Object,
      default: null
    }
  },
  emits: ['submit'],
  setup(props, { emit }) {
    const form = ref({
      nome: '',
      descricao: '',
      data_limite: '',
      finalizado: false
    })
    const focus = ref({ nome: false, descricao: false, data_limite: false })

    const resetForm = () => {
      form.value = {
        nome: '',
        descricao: '',
        data_limite: '',
        finalizado: false
      }
      focus.value = { nome: false, descricao: false, data_limite: false }
    }

    const formatDateForInput = (dateString) => {
      const date = new Date(dateString)
      return date.toISOString().slice(0, 16) // Formato YYYY-MM-DDTHH:MM
    }

    // Atualizar formulário quando task prop mudar (modo edição)
    watch(() => props.task, (newTask, oldTask) => {
      console.log('TaskForm - task prop mudou:', { newTask, oldTask })
      
      if (newTask) {
        // Modo edição
        form.value = {
          nome: newTask.nome || '',
          descricao: newTask.descricao || '',
          data_limite: newTask.data_limite ? formatDateForInput(newTask.data_limite) : '',
          finalizado: newTask.finalizado || false
        }
      } else {
        // Modo criação - reset completo
        resetForm()
      }
    }, { immediate: true })

    const handleSubmit = () => {
      console.log('TaskForm - handleSubmit chamado com dados:', form.value)
      
      const taskData = {
        nome: form.value.nome,
        descricao: form.value.descricao,
        finalizado: form.value.finalizado
      }

      // Adicionar data_limite apenas se foi preenchida
      if (form.value.data_limite) {
        taskData.data_limite = new Date(form.value.data_limite).toISOString()
      }

      console.log('TaskForm - emitindo dados:', taskData)
      emit('submit', taskData)
    }

    return {
      form,
      handleSubmit,
      focus,
      resetForm
    }
  }
}
</script>