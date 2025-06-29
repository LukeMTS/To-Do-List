import './bootstrap';
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import TasksContainer from './components/TasksContainer.vue'

const app = createApp(TasksContainer)
const pinia = createPinia()

app.use(pinia)
app.mount('#app')
