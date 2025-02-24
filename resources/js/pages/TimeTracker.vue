<template>
  <div class="min-h-screen bg-gray-50">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Timer Input -->
      <div class="bg-white rounded-lg shadow mb-6">
        <div class="p-4">
          <div class="flex items-center">
            <div class="flex-1 flex items-center">
              <div class="flex-1 flex items-center min-w-0">
                <input
                  type="text"
                  v-model="newDescription"
                  class="flex-1 min-w-0 border-0 bg-transparent text-lg font-medium text-gray-900 placeholder-gray-400 focus:ring-0"
                  placeholder="What are you working on?"
                  @keyup.enter="startTimer"
                />
                <ProjectSelector
                  v-model="selectedProjectId"
                  class="ml-2 flex-shrink-0"
                >
                  <template #trigger="{ project }">
                    <div class="flex items-center text-gray-400 hover:text-gray-600">
                      <FolderIcon class="h-5 w-5" :style="{ color: project?.color || 'currentColor' }" />
                    </div>
                  </template>
                </ProjectSelector>
              </div>
              <span class="ml-4 font-mono text-xl text-gray-700">{{ formatDuration(elapsedTime) }}</span>
            </div>
            <button
              @click="currentEntry ? stopTimer() : startTimer()"
              class="ml-4 p-2 rounded-full text-white bg-primary-500 hover:bg-primary-600 focus:outline-none"
            >
              <PlayIcon v-if="!currentEntry" class="h-6 w-6" />
              <StopIcon v-else class="h-6 w-6" />
            </button>
          </div>
        </div>
      </div>

      <!-- Time Entries -->
      <div v-if="view === 'list'" class="space-y-6">
        <div v-for="(group, date) in groupedEntries" :key="date">
          <h3 class="text-sm font-medium text-gray-500 mb-3">{{ formatDate(date) }}</h3>
          <div class="bg-white rounded-lg shadow divide-y divide-gray-100">
            <div
              v-for="entry in group"
              :key="entry.id"
              class="group p-4 flex items-center hover:bg-gray-50 transition-colors duration-150"
            >
              <!-- Description and Project -->
              <div class="flex-1 flex items-center min-w-0">
                <input
                  type="text"
                  v-model="entry.description"
                  class="min-w-0 flex-1 border-0 bg-transparent text-sm text-gray-900 placeholder-gray-400 focus:ring-0"
                  placeholder="No description"
                  @change="saveEntryChange(entry)"
                />
                <ProjectSelector
                  v-model="entry.project_id"
                  class="ml-2 flex-shrink-0 transition-opacity duration-150"
                  :class="{ 'opacity-0 group-hover:opacity-100': !entry.project_id }"
                  @update:modelValue="saveEntryChange(entry)"
                >
                  <template #trigger="{ project }">
                    <div class="flex items-center text-gray-400 hover:text-gray-600">
                      <FolderIcon 
                        class="h-4 w-4" 
                        :style="{ color: project?.color || 'currentColor' }" 
                      />
                      <span v-if="project" class="ml-2 text-sm">{{ project.name }}</span>
                    </div>
                  </template>
                </ProjectSelector>
              </div>

              <!-- Tags and Time -->
              <div class="flex items-center space-x-4 ml-4">
                <!-- Tag Selector -->
                <div class="flex-shrink-0">
                  <div class="flex items-center space-x-2">
                    <template v-if="entry.tags?.length">
                      <div v-for="tag in entry.tags" :key="tag.id" class="flex items-center space-x-1">
                        <div
                          class="w-2 h-2 rounded-full"
                          :style="{ backgroundColor: tag.color }"
                        ></div>
                        <span class="text-xs text-gray-600">{{ tag.name }}</span>
                      </div>
                    </template>
                    <button
                      @click="toggleTagSelector(entry, $event)"
                      class="text-gray-400 hover:text-gray-600 transition-opacity duration-150"
                      :class="{ 'opacity-0 group-hover:opacity-100': !entry.tags?.length }"
                    >
                      <TagIcon class="h-4 w-4" />
                    </button>
                  </div>
                  
                  <!-- Tag Dropdown -->
                  <div
                    v-if="activeTagEntry?.id === entry.id"
                    class="absolute z-10 mt-1 w-56 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                    @click.stop
                  >
                    <div class="p-2">
                      <div class="relative">
                        <input
                          type="text"
                          v-model="tagSearch"
                          class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
                          placeholder="Search or create tag"
                          @keyup.enter="createNewTag(entry)"
                        />
                      </div>

                      <div v-if="availableTags.length > 0" class="mt-2 max-h-60 overflow-auto">
                        <div
                          v-for="tag in filteredTags"
                          :key="tag.id"
                          @click="toggleEntryTag(entry, tag)"
                          class="relative flex cursor-pointer select-none items-center rounded px-2 py-2 hover:bg-gray-50"
                        >
                          <div
                            class="w-3 h-3 rounded-full mr-2"
                            :style="{ backgroundColor: tag.color }"
                          ></div>
                          <span class="block truncate">{{ tag.name }}</span>
                          <span
                            v-if="isTagSelectedForEntry(entry, tag)"
                            class="absolute inset-y-0 right-0 flex items-center pr-4 text-primary-600"
                          >
                            <CheckIcon class="h-5 w-5" />
                          </span>
                        </div>
                      </div>
                      <div v-else class="mt-2 text-sm text-gray-500 text-center py-2">
                        No tags available
                      </div>

                      <!-- Create Tag Button -->
                      <div
                        v-if="tagSearch && !hasExactTagMatch"
                        class="relative cursor-pointer select-none items-center rounded px-2 py-2 text-gray-700 hover:bg-gray-50"
                        @click="createNewTag(entry)"
                      >
                        <PlusIcon class="inline-block h-4 w-4 mr-1" />
                        Create "{{ tagSearch }}"
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Time Editor -->
                <div class="relative">
                  <button
                    @click.stop="toggleTimeEdit(entry)"
                    class="inline-flex items-center space-x-2 px-2 py-1 text-sm text-gray-600 hover:text-gray-900"
                  >
                    <span>{{ formatTime(entry.start_time) }} - {{ formatTime(entry.end_time) }}</span>
                    <span class="font-mono">{{ formatDuration(entry.duration) }}</span>
                  </button>

                  <!-- Time Edit Dropdown -->
                  <div
                    v-if="timeEditEntry?.id === entry.id"
                    class="absolute z-10 mt-1 w-80 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5"
                    @click.stop
                  >
                    <div class="p-4 space-y-3">
                      <!-- Date Picker -->
                      <div class="flex items-center justify-between">
                        <button
                          @click="changeDate(-1)"
                          class="p-1 hover:bg-gray-100 rounded-full"
                        >
                          <ChevronLeftIcon class="h-4 w-4" />
                        </button>
                        <span class="text-sm font-medium">
                          {{ formatMonthDay(timeEditDate) }}
                        </span>
                        <button
                          @click="changeDate(1)"
                          class="p-1 hover:bg-gray-100 rounded-full"
                        >
                          <ChevronRightIcon class="h-4 w-4" />
                        </button>
                      </div>

                      <!-- Time Inputs -->
                      <div class="grid grid-cols-2 gap-3">
                        <div>
                          <label class="block text-xs font-medium text-gray-700 mb-1">Start</label>
                          <input
                            type="time"
                            v-model="timeEditStartTime"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            @input="updateDurationFromTimes"
                          />
                        </div>
                        <div>
                          <label class="block text-xs font-medium text-gray-700 mb-1">End</label>
                          <input
                            type="time"
                            v-model="timeEditEndTime"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                            @input="updateDurationFromTimes"
                          />
                        </div>
                      </div>

                      <!-- Duration Input -->
                      <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Duration</label>
                        <input
                          type="text"
                          v-model="timeEditDuration"
                          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm font-mono"
                          placeholder="00:00:00"
                          @input="updateTimesFromDuration"
                        />
                      </div>

                      <!-- Actions -->
                      <div class="flex justify-end space-x-2 pt-2">
                        <button
                          @click="closeTimeEdit"
                          class="px-2 py-1 text-sm text-gray-600 hover:text-gray-900"
                        >
                          Cancel
                        </button>
                        <button
                          @click="saveTimeEdit"
                          class="px-2 py-1 text-sm bg-primary-50 text-primary-600 rounded hover:bg-primary-100"
                        >
                          Save
                        </button>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="flex items-center space-x-2 ml-4">
                  <button
                    @click="continueTimer(entry)"
                    class="p-1.5 text-gray-400 hover:text-primary-600 focus:outline-none"
                    title="Continue this timer"
                  >
                    <PlayIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="deleteEntry(entry)"
                    class="p-1.5 text-gray-400 hover:text-red-600 focus:outline-none"
                    title="Delete entry"
                  >
                    <TrashIcon class="h-4 w-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Calendar View (placeholder) -->
      <div v-else class="bg-white rounded-lg shadow p-6">
        <p class="text-center text-gray-500">Calendar view coming soon</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import axios from 'axios'
import ProjectSelector from '@/Components/ProjectSelector.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import { 
  PlayIcon, 
  StopIcon, 
  TrashIcon,
  ChartBarIcon,
  TagIcon,
  Cog6ToothIcon,
  ClockIcon,
  FolderIcon,
  CheckIcon,
  PlusIcon,
  XMarkIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  CurrencyDollarIcon,
} from '@heroicons/vue/24/outline'

// Define the layout property
defineOptions({
  layout: AppLayout
})

const currentEntry = ref(null)
const recentEntries = ref([])
const elapsedTime = ref(0)
const newDescription = ref('')
const selectedProjectId = ref(null)
const editingEntry = ref(null)
const availableTags = ref([])
const showNewTagForm = ref(false)
const newTagName = ref('')
const newTagColor = ref('#ff0000')
const view = ref('list')
const todayTotal = ref(0)
const weekTotal = ref(0)
let timer = null

const groupedEntries = computed(() => {
  const groups = {}
  recentEntries.value.forEach(entry => {
    const date = new Date(entry.start_time).toISOString().split('T')[0]
    if (!groups[date]) {
      groups[date] = []
    }
    groups[date].push(entry)
  })
  return groups
})

// Load current timer and recent entries
async function loadData() {
  try {
    await axios.get('/sanctum/csrf-cookie')
    
    const [currentResponse, entriesResponse] = await Promise.all([
      axios.get('/api/time-entries/current'),
      axios.get('/api/time-entries')
    ])
    
    currentEntry.value = currentResponse.data || null
    
    // Filter out running entries from the list
    const completedEntries = Array.isArray(entriesResponse.data) 
      ? entriesResponse.data.filter(entry => !entry.is_running)
      : [];
    recentEntries.value = processEntries(completedEntries)

    if (currentEntry.value && currentEntry.value.is_running) {
      startTimeTracking()
      // Set the description field to the current entry's description
      newDescription.value = currentEntry.value.description || ''
    } else {
      currentEntry.value = null
      elapsedTime.value = 0
      if (timer) {
        clearInterval(timer)
        timer = null
      }
    }

    calculateTotals()
  } catch (error) {
    console.error('Failed to load time entries:', error)
    recentEntries.value = []
    currentEntry.value = null
  }
}

function startTimeTracking() {
  if (!currentEntry.value || !currentEntry.value.start_time) return
  
  if (timer) {
    clearInterval(timer)
  }
  
  const startTime = new Date(currentEntry.value.start_time).getTime()
  elapsedTime.value = Math.floor((Date.now() - startTime) / 1000)
  
  timer = setInterval(() => {
    elapsedTime.value = Math.floor((Date.now() - startTime) / 1000)
  }, 1000)
}

async function startTimer() {
  try {
    await axios.get('/sanctum/csrf-cookie')
    
    const description = newDescription.value || '';
    
    console.log('Starting timer with:', {
      description,
      project_id: selectedProjectId.value,
      start_time: new Date().toISOString(),
      is_running: true,
      is_billable: selectedProjectId.value?.is_billable ?? false,
    });
    
    const response = await axios.post('/api/time-entries', {
      description,
      project_id: selectedProjectId.value,
      start_time: new Date().toISOString(),
      is_running: true,
      is_billable: selectedProjectId.value?.is_billable ?? false,
    })
    
    console.log('Timer started successfully:', response.data);
    
    currentEntry.value = response.data
    startTimeTracking()
    
    // Don't clear the description here - let loadData handle it
    await loadData()
  } catch (error) {
    console.error('Failed to start timer:', error.response?.data || error)
  }
}

async function stopTimer() {
  if (!currentEntry.value) return

  try {
    await axios.get('/sanctum/csrf-cookie')
    
    const response = await axios.post(`/api/time-entries/${currentEntry.value.id}/stop`, {
      description: newDescription.value,
      project_id: selectedProjectId.value
    })
    console.log('Timer stopped successfully:', response.data)

    if (timer) {
      clearInterval(timer)
      timer = null
    }
    
    // Clear all input fields
    currentEntry.value = null
    elapsedTime.value = 0
    newDescription.value = ''
    selectedProjectId.value = null
    
    await loadData()
  } catch (error) {
    console.error('Failed to stop timer:', error.response?.data || error)
    // If the entry was already stopped, just reload the data
    if (error.response?.status === 400) {
      currentEntry.value = null
      elapsedTime.value = 0
      if (timer) {
        clearInterval(timer)
        timer = null
      }
      await loadData()
    }
  }
}

async function continueTimer(entry) {
  try {
    await axios.get('/sanctum/csrf-cookie')
    
    const response = await axios.post('/api/time-entries', {
      description: entry.description,
      project_id: entry.project_id
    })
    
    currentEntry.value = response.data
    startTimeTracking()
    await loadData()
  } catch (error) {
    console.error('Failed to continue timer:', error)
  }
}

function startEdit(entry) {
  const startTime = new Date(entry.start_time)
  const endTime = entry.end_time ? new Date(entry.end_time) : new Date()
  
  entry.start_time_input = startTime.toTimeString().slice(0, 5)
  entry.end_time_input = endTime.toTimeString().slice(0, 5)
}

async function saveEdit() {
  if (!editingEntry.value) return

  try {
    await axios.get('/sanctum/csrf-cookie')
    
    // Get the selected date parts
    const [selectedYear, selectedMonth, selectedDay] = editingEntry.value.date.split('-').map(Number)
    
    // Get hours and minutes from the time inputs
    const [startHours, startMinutes] = editingEntry.value.start_time_input.split(':').map(Number)
    const [endHours, endMinutes] = editingEntry.value.end_time_input.split(':').map(Number)
    
    // Create dates in UTC
    const startDateTime = new Date(Date.UTC(
      selectedYear,
      selectedMonth - 1, // JavaScript months are 0-based
      selectedDay,
      startHours,
      startMinutes
    ))
    
    const endDateTime = new Date(Date.UTC(
      selectedYear,
      selectedMonth - 1,
      selectedDay,
      endHours,
      endMinutes
    ))
    
    // If end time is before start time, assume it's the next day
    if (endDateTime < startDateTime) {
      endDateTime.setUTCDate(endDateTime.getUTCDate() + 1)
    }
    
    const data = {
      description: editingEntry.value.description,
      project_id: editingEntry.value.project_id,
      start_time: startDateTime.toISOString(),
      end_time: endDateTime.toISOString(),
      tags: editingEntry.value.selectedTags.map(tag => tag.id)
    }

    await axios.put(`/api/time-entries/${editingEntry.value.id}`, data)
    
    editingEntry.value = null
    await loadData()
  } catch (error) {
    console.error('Failed to update time entry:', error)
    if (error.response?.data?.errors) {
      console.error('Validation errors:', error.response.data.errors)
    }
  }
}

function cancelEdit() {
  editingEntry.value = null
}

async function deleteEntry(entry) {
  if (!confirm('Are you sure you want to delete this time entry?')) return

  try {
    await axios.get('/sanctum/csrf-cookie')
    await axios.delete(`/api/time-entries/${entry.id}`)
    await loadData()
  } catch (error) {
    console.error('Failed to delete time entry:', error)
  }
}

function formatDuration(seconds) {
  if (!seconds && seconds !== 0) return '0:00:00'
  
  const hours = Math.floor(Math.abs(seconds) / 3600)
  const minutes = Math.floor((Math.abs(seconds) % 3600) / 60)
  const remainingSeconds = Math.abs(seconds) % 60
  
  return `${hours}:${minutes.toString().padStart(2, '0')}:${remainingSeconds.toString().padStart(2, '0')}`
}

function formatDate(dateString) {
  const date = new Date(dateString)
  const today = new Date()
  const yesterday = new Date(today)
  yesterday.setDate(yesterday.getDate() - 1)

  if (dateString === today.toISOString().split('T')[0]) {
    return 'Today'
  } else if (dateString === yesterday.toISOString().split('T')[0]) {
    return 'Yesterday'
  } else {
    return new Intl.DateTimeFormat('en-US', { 
      weekday: 'long',
      month: 'long',
      day: 'numeric'
    }).format(date)
  }
}

function formatTime(dateString) {
  if (!dateString) return ''
  return new Intl.DateTimeFormat('en-US', { 
    hour: '2-digit',
    minute: '2-digit',
    hour12: false
  }).format(new Date(dateString))
}

async function loadTags() {
  try {
    const response = await axios.get('/api/tags')
    availableTags.value = response.data || []
  } catch (error) {
    availableTags.value = []
  }
}

function isTagSelected(tag) {
  return editingEntry.value?.selectedTags.some(t => t.id === tag.id)
}

function toggleTag(tag) {
  if (!editingEntry.value) return
  
  const index = editingEntry.value.selectedTags.findIndex(t => t.id === tag.id)
  if (index === -1) {
    editingEntry.value.selectedTags.push(tag)
  } else {
    editingEntry.value.selectedTags.splice(index, 1)
  }
}

async function createTag() {
  if (!newTagName.value.trim()) return
  
  try {
    const response = await axios.post('/api/tags', {
      name: newTagName.value.trim(),
      color: newTagColor.value
    })
    
    availableTags.value.push(response.data)
    newTagName.value = ''
    newTagColor.value = '#ff0000'
    showNewTagForm.value = false
  } catch (error) {
    console.error('Failed to create tag:', error)
  }
}

// Calculate totals when entries change
const calculateTotals = () => {
  if (!Array.isArray(recentEntries.value)) {
    todayTotal.value = 0;
    weekTotal.value = 0;
    return;
  }

  const now = new Date();
  const today = now.toISOString().split('T')[0];
  const weekStart = new Date(now.setDate(now.getDate() - now.getDay())).toISOString().split('T')[0];

  todayTotal.value = 0;
  weekTotal.value = 0;

  recentEntries.value.forEach(entry => {
    if (!entry || !entry.start_time) return;

    const entryDate = entry.start_time.split('T')[0];
    const duration = entry.duration || 0;

    if (entryDate === today) {
      todayTotal.value += duration;
    }
    if (entryDate >= weekStart) {
      weekTotal.value += duration;
    }
  });
};

// New function to save entry changes
const saveEntryChange = async (entry) => {
  try {
    await axios.put(`/api/time-entries/${entry.id}`, {
      description: entry.description,
      project_id: entry.project_id,
      start_time: entry.start_time,
      end_time: entry.end_time,
      tags: entry.tags?.map(tag => tag.id) || []
    })
    await loadData()
  } catch (error) {
    console.error('Failed to update entry:', error)
  }
}

// New function to save entry time changes
const saveEntryTime = async (entry) => {
  try {
    const startDate = new Date(entry.start_time)
    const [startHours, startMinutes] = entry.start_time_input.split(':').map(Number)
    const [endHours, endMinutes] = entry.end_time_input.split(':').map(Number)
    
    const startDateTime = new Date(Date.UTC(
      startDate.getUTCFullYear(),
      startDate.getUTCMonth(),
      startDate.getUTCDate(),
      startHours,
      startMinutes
    ))
    
    const endDateTime = new Date(Date.UTC(
      startDate.getUTCFullYear(),
      startDate.getUTCMonth(),
      startDate.getUTCDate(),
      endHours,
      endMinutes
    ))
    
    if (endDateTime < startDateTime) {
      endDateTime.setUTCDate(endDateTime.getUTCDate() + 1)
    }
    
    await axios.put(`/api/time-entries/${entry.id}`, {
      description: entry.description,
      project_id: entry.project_id,
      start_time: startDateTime.toISOString(),
      end_time: endDateTime.toISOString(),
      tags: entry.tags?.map(tag => tag.id) || []
    })
    await loadData()
  } catch (error) {
    console.error('Failed to update entry time:', error)
  }
}

// Process entries after loading to add time inputs
const processEntries = (entries) => {
  if (!Array.isArray(entries)) return [];
  return entries.map(entry => {
    if (entry) {
      startEdit(entry);
    }
    return entry;
  });
}

// Add these refs after existing ones
const activeTagEntry = ref(null)
const tagSearch = ref('')
const editingDurationEntry = ref(null)
const durationInput = ref('')

// Add these computed properties
const filteredTags = computed(() => {
  console.log('Available tags:', availableTags.value)
  if (!tagSearch.value) return availableTags.value
  const query = tagSearch.value.toLowerCase()
  return availableTags.value.filter(tag => 
    tag.name.toLowerCase().includes(query)
  )
})

const hasExactTagMatch = computed(() => {
  if (!tagSearch.value) return false
  return availableTags.value.some(tag => 
    tag.name.toLowerCase() === tagSearch.value.toLowerCase()
  )
})

// Add these new functions
function toggleTagSelector(entry, event) {
  event?.stopPropagation()
  if (activeTagEntry.value?.id === entry.id) {
    closeTagDropdown()
  } else {
    activeTagEntry.value = entry
    tagSearch.value = ''
    loadTags()
  }
}

function isTagSelectedForEntry(entry, tag) {
  return entry.tags?.some(t => t.id === tag.id)
}

async function toggleEntryTag(entry, tag) {
  const tags = [...(entry.tags || [])]
  const index = tags.findIndex(t => t.id === tag.id)
  
  if (index === -1) {
    tags.push(tag)
  } else {
    tags.splice(index, 1)
  }
  
  try {
    await axios.put(`/api/time-entries/${entry.id}`, {
      ...entry,
      tags: tags.map(t => t.id)
    })
    await loadData()
  } catch (error) {
    console.error('Failed to update entry tags:', error)
  }
}

async function createNewTag(entry) {
  if (!tagSearch.value.trim()) return
  
  try {
    const response = await axios.post('/api/tags', {
      name: tagSearch.value.trim(),
      color: `#${Math.floor(Math.random()*16777215).toString(16).padStart(6, '0')}`
    })
    
    availableTags.value.push(response.data)
    await toggleEntryTag(entry, response.data)
    tagSearch.value = ''
  } catch (error) {
    console.error('Failed to create tag:', error)
  }
}

function toggleDurationEdit(entry) {
  if (editingDurationEntry.value?.id === entry.id) {
    cancelDurationEdit()
  } else {
    editingDurationEntry.value = entry
    const hours = Math.floor(entry.duration / 3600)
    const minutes = Math.floor((entry.duration % 3600) / 60)
    durationInput.value = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`
  }
}

function cancelDurationEdit() {
  editingDurationEntry.value = null
  durationInput.value = ''
}

async function saveDuration(entry) {
  const [hours, minutes, seconds] = durationInput.value.split(':').map(Number)
  const totalSeconds = (hours * 3600) + (minutes * 60) + seconds
  const startTime = new Date(entry.start_time)
  const endTime = new Date(startTime.getTime() + (totalSeconds * 1000))
  
  try {
    await axios.put(`/api/time-entries/${entry.id}`, {
      ...entry,
      start_time: startTime.toISOString(),
      end_time: endTime.toISOString()
    })
    editingDurationEntry.value = null
    await loadData()
  } catch (error) {
    console.error('Failed to update duration:', error)
  }
}

function formatDurationInput() {
  const [hours, minutes, seconds] = durationInput.value.split(':').map(Number)
  const totalSeconds = (hours * 3600) + (minutes * 60) + seconds
  const formattedDuration = formatDuration(totalSeconds)
  durationInput.value = formattedDuration.split(':').slice(0, 3).join(':')
}

onMounted(() => {
  loadData()
  loadTags()
  
  // Add click outside handlers
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.relative')) {
      closeTagDropdown()
      closeTimeEdit()
    }
  })
})

onUnmounted(() => {
  if (timer) {
    clearInterval(timer)
  }
  // Remove click outside handlers
  document.removeEventListener('click', closeTagDropdown)
  document.removeEventListener('click', closeTimeEdit)
})

// Add the close function
function closeTagDropdown() {
  activeTagEntry.value = null
  tagSearch.value = ''
}

// Replace timeEditModal ref with these simpler refs
const timeEditEntry = ref(null)
const timeEditDate = ref(new Date())
const timeEditStartTime = ref('')
const timeEditEndTime = ref('')
const timeEditDuration = ref('')

// Replace openTimeEditModal with this simpler toggle function
function toggleTimeEdit(entry) {
  if (timeEditEntry.value?.id === entry.id) {
    closeTimeEdit()
    return
  }

  const date = new Date(entry.start_time)
  timeEditDate.value = date
  timeEditStartTime.value = date.toTimeString().slice(0, 5)
  timeEditEndTime.value = new Date(entry.end_time).toTimeString().slice(0, 5)
  timeEditDuration.value = formatDuration(entry.duration)
  timeEditEntry.value = entry
}

function closeTimeEdit() {
  timeEditEntry.value = null
  timeEditStartTime.value = ''
  timeEditEndTime.value = ''
  timeEditDuration.value = ''
}

function changeDate(delta) {
  const newDate = new Date(timeEditDate.value)
  newDate.setDate(newDate.getDate() + delta)
  timeEditDate.value = newDate
}

function formatMonthDay(date) {
  return new Intl.DateTimeFormat('en-US', { 
    weekday: 'short',
    month: 'short',
    day: 'numeric'
  }).format(date)
}

// Update the save function to use the new refs
async function saveTimeEdit() {
  if (!timeEditEntry.value) return

  const [startHours, startMinutes] = timeEditStartTime.value.split(':').map(Number)
  const [endHours, endMinutes] = timeEditEndTime.value.split(':').map(Number)
  
  const startDateTime = new Date(timeEditDate.value)
  startDateTime.setHours(startHours, startMinutes, 0)
  
  const endDateTime = new Date(timeEditDate.value)
  endDateTime.setHours(endHours, endMinutes, 0)
  
  if (endDateTime < startDateTime) {
    endDateTime.setDate(endDateTime.getDate() + 1)
  }
  
  try {
    await axios.put(`/api/time-entries/${timeEditEntry.value.id}`, {
      description: timeEditEntry.value.description,
      project_id: timeEditEntry.value.project_id,
      start_time: startDateTime.toISOString(),
      end_time: endDateTime.toISOString(),
      tags: timeEditEntry.value.tags?.map(tag => tag.id) || []
    })
    
    closeTimeEdit()
    await loadData()
  } catch (error) {
    console.error('Failed to update time entry:', error)
  }
}

// Add these functions after the formatMonthDay function
function updateDurationFromTimes() {
  if (!timeEditStartTime.value || !timeEditEndTime.value) return

  const [startHours, startMinutes] = timeEditStartTime.value.split(':').map(Number)
  const [endHours, endMinutes] = timeEditEndTime.value.split(':').map(Number)
  
  const startDate = new Date(timeEditDate.value)
  startDate.setHours(startHours, startMinutes, 0)
  
  const endDate = new Date(timeEditDate.value)
  endDate.setHours(endHours, endMinutes, 0)
  
  if (endDate < startDate) {
    endDate.setDate(endDate.getDate() + 1)
  }
  
  const durationInSeconds = Math.floor((endDate - startDate) / 1000)
  timeEditDuration.value = formatDuration(durationInSeconds)
}

function updateTimesFromDuration() {
  if (!timeEditDuration.value || !timeEditStartTime.value) return

  const [hours, minutes, seconds] = timeEditDuration.value.split(':').map(Number)
  const durationInSeconds = (hours * 3600) + (minutes * 60) + (seconds || 0)
  
  const [startHours, startMinutes] = timeEditStartTime.value.split(':').map(Number)
  const startDate = new Date(timeEditDate.value)
  startDate.setHours(startHours, startMinutes, 0)
  
  const endDate = new Date(startDate.getTime() + (durationInSeconds * 1000))
  timeEditEndTime.value = endDate.toTimeString().slice(0, 5)
}
</script> 