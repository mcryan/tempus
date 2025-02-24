<template>
  <div class="relative">
    <button
      type="button"
      @click="isOpen = !isOpen"
      class="inline-flex items-center text-left text-gray-500 hover:text-gray-700"
    >
      <div
        v-if="selectedProject"
        class="w-3 h-3 rounded-full mr-2"
        :style="{ backgroundColor: selectedProject.color }"
      ></div>
      <FolderIcon 
        v-if="!selectedProject" 
        class="h-5 w-5 text-gray-400" 
      />
      <span v-if="selectedProject" class="ml-2">{{ selectedProject.name }}</span>
    </button>

    <div
      v-if="isOpen"
      class="absolute z-10 mt-1 w-56 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
    >
      <div class="p-2">
        <!-- Search/Create Input -->
        <div class="relative">
          <input
            type="text"
            v-model="searchQuery"
            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
            placeholder="Find/Create Project"
            @keyup.enter="createProject"
          />
        </div>

        <!-- Project List -->
        <div class="mt-2 max-h-60 overflow-auto">
          <div
            v-for="project in filteredProjects"
            :key="project.id"
            @click="selectProject(project)"
            class="relative flex cursor-pointer select-none items-center rounded px-2 py-2 hover:bg-gray-50"
          >
            <div
              class="w-3 h-3 rounded-full mr-2"
              :style="{ backgroundColor: project.color }"
            ></div>
            <span class="block truncate">{{ project.name }}</span>
            <span
              v-if="modelValue === project.id"
              class="absolute inset-y-0 right-0 flex items-center pr-4 text-primary-600"
            >
              <CheckIcon class="h-5 w-5" />
            </span>
          </div>
        </div>

        <!-- Create Project Button -->
        <div
          v-if="searchQuery && !hasExactMatch"
          class="relative cursor-pointer select-none items-center rounded px-2 py-2 text-gray-700 hover:bg-gray-50"
          @click="createProject"
        >
          <PlusIcon class="inline-block h-4 w-4 mr-1" />
          Create "{{ searchQuery }}"
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { CheckIcon, PlusIcon, FolderIcon } from '@heroicons/vue/20/solid'
import axios from 'axios'
import { useProjects } from '@/composables/useProjects'

const props = defineProps({
  modelValue: {
    type: Number,
    default: null
  }
})

const emit = defineEmits(['update:modelValue'])

const { projects, isLoading, fetchProjects } = useProjects()

const isOpen = ref(false)
const searchQuery = ref('')
const selectedProject = ref(null)

const filteredProjects = computed(() => {
  if (!Array.isArray(projects.value)) return []
  if (!searchQuery.value) return projects.value
  const query = searchQuery.value.toLowerCase()
  return projects.value.filter(project => 
    project?.name?.toLowerCase().includes(query)
  )
})

const hasExactMatch = computed(() => {
  if (!Array.isArray(projects.value)) return false
  if (!searchQuery.value) return false
  return projects.value.some(project => 
    project?.name?.toLowerCase() === searchQuery.value.toLowerCase()
  )
})

watch(() => props.modelValue, async (newValue) => {
  if (newValue && Array.isArray(projects.value)) {
    selectedProject.value = projects.value.find(p => p?.id === newValue) || null
  } else {
    selectedProject.value = null
  }
})

watch(projects, (newProjects) => {
  console.log('Projects changed:', newProjects)
  console.log('Current modelValue:', props.modelValue)
  if (props.modelValue && Array.isArray(newProjects)) {
    selectedProject.value = newProjects.find(p => p.id === props.modelValue)
    console.log('Updated selected project:', selectedProject.value)
  }
}, { immediate: true })

function selectProject(project) {
  emit('update:modelValue', project.id)
  isOpen.value = false
  searchQuery.value = ''
}

async function createProject() {
  if (!searchQuery.value || hasExactMatch.value) return

  try {
    await axios.get('/sanctum/csrf-cookie')
    
    const response = await axios.post('/api/projects', {
      name: searchQuery.value,
      color: `#${Math.floor(Math.random()*16777215).toString(16).padStart(6, '0')}`,
      is_active: true,
      is_billable: false
    })
    
    projects.value.push(response.data)
    selectProject(response.data)
  } catch (error) {
    console.error('Failed to create project:', error)
  }
}

function closeDropdown(e) {
  if (!e.target.closest('.relative')) {
    isOpen.value = false
  }
}

onMounted(async () => {
  console.log('ProjectSelector mounted, modelValue:', props.modelValue)
  const projectsData = await fetchProjects()
  console.log('Projects loaded:', projectsData)
  if (props.modelValue) {
    selectedProject.value = projectsData.find(p => p.id === props.modelValue)
    console.log('Selected project:', selectedProject.value)
  }
  document.addEventListener('click', closeDropdown)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown)
})
</script> 