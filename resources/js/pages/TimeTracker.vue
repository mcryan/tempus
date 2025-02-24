<template>
  <div class="w-full">
    <div class="w-full py-4">
      <!-- Timer Input -->
      <div class="bg-white rounded-lg shadow-md mb-6">
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

      <!-- Add this right after the Timer Input section and before the views -->
      <div class="mb-4 flex justify-between items-center">
        <div class="flex space-x-2">
          <button
            @click="view = 'list'"
            class="px-2.5 py-1.5 rounded-md text-sm font-medium"
            :class="view === 'list' ? 'bg-primary-100 text-primary-700' : 'text-gray-500 hover:text-gray-700'"
          >
            <ListBulletIcon class="h-5 w-5 inline-block mr-1" />
            List
          </button>
          <button
            @click="view = 'calendar'"
            class="px-2.5 py-1.5 rounded-md text-sm font-medium"
            :class="view === 'calendar' ? 'bg-primary-100 text-primary-700' : 'text-gray-500 hover:text-gray-700'"
          >
            <CalendarIcon class="h-5 w-5 inline-block mr-1" />
            Calendar
          </button>
        </div>
        
        <!-- Week Navigation -->
        <div class="flex items-center space-x-2">
          <button
            @click="previousWeek"
            class="p-1.5 rounded-full text-gray-400 hover:text-gray-600"
            v-if="view === 'calendar'"
          >
            <ChevronLeftIcon class="h-5 w-5" />
          </button>
          <span 
            class="text-sm font-medium text-gray-700 flex items-center px-2 min-w-[150px] justify-center" 
            v-if="view === 'calendar'"
          >
            {{ formatWeekRange }}
          </span>
          <button
            @click="nextWeek"
            class="p-1.5 rounded-full text-gray-400 hover:text-gray-600"
            v-if="view === 'calendar'"
          >
            <ChevronRightIcon class="h-5 w-5" />
          </button>
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
              :data-entry-id="entry.id"
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
                    @click.stop="toggleTimeEdit(entry, $event)"
                    class="inline-flex items-center space-x-2 px-2 py-1 text-sm text-gray-600 hover:text-gray-900"
                  >
                    <span>{{ formatTime(draggedEntry?.id === entry.id && dragPreviewTimes ? dragPreviewTimes.start : entry.start_time) }} - {{ formatTime(draggedEntry?.id === entry.id && dragPreviewTimes ? dragPreviewTimes.end : entry.end_time) }}</span>
                    <span class="font-mono">{{ formatDuration(entry.duration) }}</span>
                  </button>

                  <!-- Time Edit Dropdown -->
                  <div
                    v-if="timeEditEntry?.id === entry.id"
                    class="absolute z-[100] w-80 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 time-edit-dropdown overflow-visible"
                    :style="getTimeEditPosition($event)"
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

      <!-- Replace the existing calendar view placeholder with this -->
      <div v-if="view === 'calendar'" class="bg-white rounded-lg shadow">
        <!-- Calendar Header -->
        <div class="grid grid-cols-7 border-b">
          <div
            v-for="day in weekDays"
            :key="day.date"
            class="px-2 py-3 text-center border-r last:border-r-0"
          >
            <div class="text-xs font-medium text-gray-500">
              {{ formatWeekDay(day.date) }}
            </div>
            <div class="text-sm font-semibold" :class="isToday(day.date) ? 'text-primary-600' : 'text-gray-900'">
              {{ formatDayNumber(day.date) }}
            </div>
          </div>
        </div>
        
        <!-- Calendar Grid -->
        <div 
          class="grid grid-cols-7 relative"
          :style="{
            height: 'calc(90vh - 8rem)',
            minHeight: '800px',
            paddingBottom: '4rem'
          }"
        >
          <!-- Time indicators -->
          <div 
            class="absolute left-0 top-0 h-[calc(100%-4rem)] w-16 border-r text-xs z-50 bg-white select-none"
          >
            <div
              v-for="hour in 25"
              :key="hour"
              class="absolute w-full flex items-center justify-end pr-2 text-gray-500 -translate-y-1/2"
              :style="{ top: `${((hour - 1) / 24 * 100)}%` }"
            >
              {{ formatHour(hour <= 24 ? hour - 1 : 24) }}
            </div>
          </div>
          
          <!-- Day columns -->
          <div
            v-for="day in weekDays"
            :key="day.date"
            :data-date="day.date"
            class="relative border-r last:border-r-0 transition-colors duration-150 pt-[1px]"
            :class="{ 'bg-gray-50': draggedEntry }"
            @dragover="handleDragOver"
            @drop="handleDrop($event, day.date)"
          >
            <!-- Hour grid lines -->
            <div
              v-for="hour in 24"
              :key="hour"
              class="absolute w-full border-t border-gray-100"
              :style="{ top: `${((hour - 1) / 24 * 100)}%`, height: '1px' }"
            >
              <!-- 15-minute interval guides -->
              <div class="absolute w-full border-t border-gray-50" style="top: 25%"></div>
              <div class="absolute w-full border-t border-gray-50" style="top: 50%"></div>
              <div class="absolute w-full border-t border-gray-50" style="top: 75%"></div>
            </div>

            <!-- Time entries -->
            <div
              v-for="entry in getEntriesForDay(day.date)"
              :key="entry.id"
              :data-entry-id="entry.id"
              class="absolute left-2 right-2 px-2 py-1.5 text-xs cursor-pointer overflow-hidden transition-opacity duration-150 group flex flex-col h-full"
              :style="resizingEntry?.id === entry.id ? getResizeStyle(entry, day.date) : getEntryStyle(entry, day.date)"
              :class="[
                getEntryColorClass(entry),
                { 'opacity-50': draggedEntry?.id === entry.id }
              ]"
              @click.stop="toggleTimeEdit(entry, $event)"
              draggable="true"
              @dragstart="handleDragStart($event, entry)"
              @dragend="handleDragEnd"
            >
              <!-- Top resize handle -->
              <div 
                class="absolute -top-1 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover:opacity-100 bg-black/10 hover:bg-black/20"
                @mousedown.prevent="startResize($event, entry, 'top')"
                @dragstart.prevent
              ></div>
              
              <!-- Description -->
              <div class="font-medium truncate">
                <span 
                  v-if="entry.project?.color" 
                  class="inline-block w-2 h-2 rounded-full mr-1"
                  :style="{ backgroundColor: entry.project.color }"
                ></span>
                {{ entry.description || 'No description' }}
              </div>
              
              <!-- Project name -->
              <div class="text-xs opacity-75 truncate" :style="{ color: entry.project?.color || 'currentColor' }">
                {{ entry.project?.name || '' }}
              </div>
              
              <!-- Times -->
              <div class="text-xs opacity-75 mt-0.5">
                {{ formatTime((draggedEntry?.id === entry.id || resizingEntry?.id === entry.id) && dragPreviewTimes ? dragPreviewTimes.start : entry.start_time) }} - 
                {{ formatTime((draggedEntry?.id === entry.id || resizingEntry?.id === entry.id) && dragPreviewTimes ? dragPreviewTimes.end : entry.end_time) }}
              </div>
              
              <!-- Duration at bottom -->
              <div class="text-xs opacity-75 font-mono mt-auto">
                {{ formatDuration(getDurationForEntry(entry)) }}
              </div>
              
              <!-- Bottom resize handle -->
              <div 
                class="absolute -bottom-1 left-0 right-0 h-2 cursor-ns-resize opacity-0 group-hover:opacity-100 bg-black/10 hover:bg-black/20"
                @mousedown.prevent="startResize($event, entry, 'bottom')"
                @dragstart.prevent
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Global Time Edit Dropdown -->
    <div
      v-if="timeEditEntry"
      class="fixed z-[100] w-80 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 time-edit-dropdown overflow-visible"
      :style="getTimeEditPosition($event)"
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
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import axios from 'axios'
import { Link } from '@inertiajs/vue3'
import ProjectSelector from '@/Components/ProjectSelector.vue'
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
  ListBulletIcon,
  CalendarIcon,
} from '@heroicons/vue/24/outline'

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
const view = ref(localStorage.getItem('timeTrackerView') || 'list')
const todayTotal = ref(0)
const weekTotal = ref(0)
const currentWeekStart = ref(localStorage.getItem('timeTrackerWeek') || getWeekStart(new Date()))
let timer = null

// Watch for view changes and save to localStorage
watch(view, (newView) => {
  localStorage.setItem('timeTrackerView', newView)
})

// Also add currentWeekStart to localStorage
// const currentWeekStart = ref(getWeekStart(new Date()))

// Watch for week changes and save to localStorage
watch(currentWeekStart, (newWeek) => {
  localStorage.setItem('timeTrackerWeek', newWeek)
})

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

const weekDays = computed(() => {
  const days = []
  const start = new Date(currentWeekStart.value)
  
  for (let i = 0; i < 7; i++) {
    const date = new Date(start)
    date.setDate(date.getDate() + i)
    days.push({ date: date.toISOString().split('T')[0] })
  }
  
  return days
})

const formatWeekRange = computed(() => {
  const start = new Date(currentWeekStart.value)
  const end = new Date(start)
  end.setDate(end.getDate() + 6)
  
  const startMonth = start.toLocaleString('default', { month: 'short' })
  const endMonth = end.toLocaleString('default', { month: 'short' })
  
  if (startMonth === endMonth) {
    return `${startMonth} ${start.getDate()} - ${end.getDate()}, ${start.getFullYear()}`
  }
  
  return `${startMonth} ${start.getDate()} - ${endMonth} ${end.getDate()}, ${start.getFullYear()}`
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
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  if (timer) {
    clearInterval(timer)
  }
  // Remove click outside handlers
  document.removeEventListener('click', handleClickOutside)
})

function handleClickOutside(event) {
  const timeEditElement = document.querySelector('.time-edit-dropdown')
  if (timeEditElement && !timeEditElement.contains(event.target)) {
    closeTimeEdit()
  }
}

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
function toggleTimeEdit(entry, event) {
  if (justFinishedResize.value) return

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

// Keep these calendar-related functions
function getWeekStart(date) {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  const day = d.getDay()
  d.setDate(d.getDate() - day)
  return d.toISOString().split('T')[0]
}

function nextWeek() {
  const date = new Date(currentWeekStart.value)
  date.setDate(date.getDate() + 7)
  currentWeekStart.value = date.toISOString().split('T')[0]
}

function previousWeek() {
  const date = new Date(currentWeekStart.value)
  date.setDate(date.getDate() - 7)
  currentWeekStart.value = date.toISOString().split('T')[0]
}

function formatWeekDay(dateString) {
  return new Date(dateString).toLocaleDateString('default', { weekday: 'short' })
}

function formatDayNumber(dateString) {
  return new Date(dateString).getDate()
}

function formatHour(hour) {
  if (hour < 0) return '00:00'
  if (hour === 24) return '24:00'
  return `${hour.toString().padStart(2, '0')}:00`
}

function isToday(dateString) {
  const today = new Date().toISOString().split('T')[0]
  return dateString === today
}

function getEntriesForDay(dateString) {
  return recentEntries.value.filter(entry => {
    // Create dates at start of day in local timezone
    const dayStart = new Date(dateString + 'T00:00:00')
    const dayEnd = new Date(dateString + 'T23:59:59')
    
    // Parse entry times and ensure they're in the same timezone
    const entryStart = new Date(entry.start_time)
    const entryEnd = new Date(entry.end_time)
    
    // Check if any part of the entry falls within this day
    const startsBeforeEnd = entryStart <= dayEnd
    const endsAfterStart = entryEnd >= dayStart
    
    return startsBeforeEnd && endsAfterStart
  })
}

function getEntryStyle(entry, date) {
  const startTime = new Date(entry.start_time)
  const endTime = new Date(entry.end_time)
  const dayStart = new Date(date + 'T00:00:00')
  const dayEnd = new Date(date + 'T23:59:59')
  
  // Calculate start percent based on whether the entry starts on this day
  const startPercent = startTime < dayStart 
    ? 0 
    : ((startTime.getHours() + startTime.getMinutes() / 60) / 24 * 100)
  
  // Calculate end percent based on whether the entry ends on this day
  const endPercent = endTime > dayEnd
    ? 100  // Fill to end of day
    : ((endTime.getHours() + endTime.getMinutes() / 60) / 24 * 100)
  
  const heightPercent = endPercent - startPercent
  
  // Get the project color or default to gray
  const color = entry.project?.color || '#E5E7EB'
  
  // Convert hex to RGB for transparency
  const rgb = hexToRgb(color)
  
  return {
    top: `${startPercent}%`,
    height: `${heightPercent}%`,
    backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.15)`,
    borderColor: color,
  }
}

function getEntryColorClass(entry) {
  // Default colors for entries without a project
  if (!entry.project?.color) {
    return 'bg-gray-100 text-gray-700 border border-gray-200'
  }
  
  // Return dynamic classes based on project color
  return {
    'border rounded shadow-sm': true,
    [`hover:shadow-md transition-shadow duration-150`]: true,
    'text-gray-900': true,
  }
}

function editEntry(entry, event) {
  toggleTimeEdit(entry, event)
}

// Helper function to convert hex color to RGB
function hexToRgb(hex) {
  // Remove # if present
  hex = hex.replace('#', '')
  
  // Convert 3-digit hex to 6-digits
  if (hex.length === 3) {
    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2]
  }
  
  // Convert hex to RGB
  const r = parseInt(hex.substring(0, 2), 16)
  const g = parseInt(hex.substring(2, 4), 16)
  const b = parseInt(hex.substring(4, 6), 16)
  
  return { r, g, b }
}

function getTimeEditPosition(event) {
  // Get the parent element's position
  const element = document.querySelector(`[data-entry-id="${timeEditEntry.value.id}"]`)
  if (!element) return {}
  const rect = element.getBoundingClientRect()
  
  const viewportHeight = window.innerHeight
  const viewportWidth = window.innerWidth
  const dropdownHeight = 400
  const dropdownWidth = 320
  const padding = 8
  
  // Position dropdown aligned with the right edge of the time display
  let left = rect.right - dropdownWidth
  let top = rect.bottom + 5 // 5px gap
  
  // If dropdown would go off the left side of the screen
  if (left < padding) {
    left = padding
  }
  
  // If dropdown would go off the right side of the screen
  if (left + dropdownWidth > viewportWidth - padding) {
    left = viewportWidth - dropdownWidth - padding
  }
  
  // Check if dropdown would go below viewport
  if (top + dropdownHeight > viewportHeight - padding) {
    // Position above the clicked element instead
    top = rect.top - dropdownHeight - 5
  }
  
  return {
    position: 'fixed',
    top: `${top}px`,
    left: `${left}px`,
    maxHeight: `${dropdownHeight}px`,
    maxWidth: `${dropdownWidth}px`
  }
}

// Add these refs at the top with other refs
const draggedEntry = ref(null)
const dragStartOffset = ref(null)

// Add these refs
const dragPreviewTimes = ref(null)

// Add these refs for resize functionality
const resizingEntry = ref(null)
const resizeEdge = ref(null)
const resizeStartY = ref(null)
const originalTimes = ref(null)

// Add this ref with the other refs
const justFinishedResize = ref(false)

// Add these new functions
function handleDragStart(event, entry) {
  draggedEntry.value = entry
  const rect = event.target.getBoundingClientRect()
  dragStartOffset.value = event.clientY - rect.top
  
  // Add visual feedback
  event.target.classList.add('opacity-50')
  
  // Set required data for drag operation
  event.dataTransfer.effectAllowed = 'move'
  
  // Initialize preview times
  dragPreviewTimes.value = {
    start: new Date(entry.start_time),
    end: new Date(entry.end_time)
  }
}

function handleDragEnd(event) {
  event.target.classList.remove('opacity-50')
  draggedEntry.value = null
  dragStartOffset.value = null
  dragPreviewTimes.value = null
}

function handleDragOver(event) {
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
  
  if (!draggedEntry.value) return
  
  // Calculate preview times
  const columnRect = event.currentTarget.getBoundingClientRect()
  const minutesInDay = 24 * 60
  const pixelsPerMinute = columnRect.height / minutesInDay
  
  // Calculate the new start time based on drop position minus the original click offset
  const dropY = event.clientY - columnRect.top - dragStartOffset.value
  let startMinutes = Math.floor(dropY / pixelsPerMinute)
  
  // Snap to nearest 15-minute interval
  const interval = 15
  startMinutes = Math.round(startMinutes / interval) * interval
  startMinutes = Math.max(0, Math.min(1440, startMinutes))
  
  // Update preview times
  const date = event.currentTarget.dataset.date
  const previewStart = new Date(date)
  previewStart.setHours(Math.floor(startMinutes / 60), startMinutes % 60, 0, 0)
  
  const durationMinutes = draggedEntry.value.duration / 60
  const previewEnd = new Date(previewStart)
  previewEnd.setMinutes(previewEnd.getMinutes() + durationMinutes)
  
  dragPreviewTimes.value = {
    start: previewStart,
    end: previewEnd
  }
}

async function handleDrop(event, date) {
  event.preventDefault()
  
  if (!draggedEntry.value) return
  
  // Calculate the time based on the drop position
  const columnRect = event.currentTarget.getBoundingClientRect()
  const minutesInDay = 24 * 60
  const pixelsPerMinute = columnRect.height / minutesInDay
  
  // Calculate the new start time based on drop position minus the original click offset
  const dropY = event.clientY - columnRect.top - dragStartOffset.value
  let startMinutes = Math.floor(dropY / pixelsPerMinute)
  
  // Snap to nearest 15-minute interval
  const interval = 15
  startMinutes = Math.round(startMinutes / interval) * interval
  
  // Calculate original duration
  const originalStart = new Date(draggedEntry.value.start_time)
  const originalEnd = new Date(draggedEntry.value.end_time)
  const durationMinutes = Math.round((originalEnd - originalStart) / (1000 * 60))
  
  // Only limit the start time to be within the day (0-1440 minutes)
  startMinutes = Math.max(0, Math.min(1440, startMinutes))
  
  // Create new start time
  const startDateTime = new Date(date)
  const hours = Math.floor(startMinutes / 60)
  const minutes = startMinutes % 60
  startDateTime.setHours(hours, minutes, 0, 0)
  
  // Calculate new end time maintaining the same duration
  const endDateTime = new Date(startDateTime)
  endDateTime.setMinutes(endDateTime.getMinutes() + durationMinutes)
  
  // Allow entries to span across midnight
  // No need to adjust the times as we want to preserve the duration
  
  try {
    await axios.put(`/api/time-entries/${draggedEntry.value.id}`, {
      description: draggedEntry.value.description,
      project_id: draggedEntry.value.project_id,
      start_time: startDateTime.toISOString(),
      end_time: endDateTime.toISOString(),
      tags: draggedEntry.value.tags?.map(tag => tag.id) || []
    })
    
    await loadData()
  } catch (error) {
    console.error('Failed to update time entry:', error)
  }
}

function startResize(event, entry, edge) {
  event.stopPropagation()
  resizingEntry.value = entry
  resizeEdge.value = edge
  resizeStartY.value = event.clientY
  originalTimes.value = {
    start: new Date(entry.start_time),
    end: new Date(entry.end_time)
  }
  
  document.addEventListener('mousemove', handleResize)
  document.addEventListener('mouseup', stopResize)
}

function handleResize(event) {
  if (!resizingEntry.value || !resizeStartY.value) return
  
  const columnElement = event.target.closest('[data-date]')
  if (!columnElement) return
  
  const columnRect = columnElement.getBoundingClientRect()
  const minutesInDay = 24 * 60
  const pixelsPerMinute = columnRect.height / minutesInDay
  
  const deltaY = event.clientY - resizeStartY.value
  const deltaMinutes = Math.round(deltaY / pixelsPerMinute / 15) * 15
  
  const newTimes = {
    start: new Date(originalTimes.value.start),
    end: new Date(originalTimes.value.end)
  }
  
  if (resizeEdge.value === 'top') {
    const newStart = new Date(originalTimes.value.start)
    newStart.setMinutes(newStart.getMinutes() + deltaMinutes)
    if (newStart < newTimes.end) {
      newTimes.start = newStart
    }
  } else {
    const newEnd = new Date(originalTimes.value.end)
    newEnd.setMinutes(newEnd.getMinutes() + deltaMinutes)
    if (newEnd > newTimes.start) {
      newTimes.end = newEnd
    }
  }
  
  dragPreviewTimes.value = newTimes
}

function stopResize() {
  if (!resizingEntry.value || !dragPreviewTimes.value) return
  
  // Save the changes
  axios.put(`/api/time-entries/${resizingEntry.value.id}`, {
    description: resizingEntry.value.description,
    project_id: resizingEntry.value.project_id,
    start_time: dragPreviewTimes.value.start.toISOString(),
    end_time: dragPreviewTimes.value.end.toISOString(),
    tags: resizingEntry.value.tags?.map(tag => tag.id) || []
  })
  .then(() => loadData())
  .catch(error => console.error('Failed to update time entry:', error))
  
  // Clean up
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
  
  // Set the flag and clear it after a short delay
  justFinishedResize.value = true
  setTimeout(() => {
    justFinishedResize.value = false
  }, 100)
  
  resizingEntry.value = null
  resizeEdge.value = null
  resizeStartY.value = null
  originalTimes.value = null
  dragPreviewTimes.value = null
}

// Clean up event listeners on component unmount
onUnmounted(() => {
  document.removeEventListener('mousemove', handleResize)
  document.removeEventListener('mouseup', stopResize)
})

function getResizeStyle(entry, date) {
  const dayStart = new Date(date + 'T00:00:00')
  const dayEnd = new Date(date + 'T23:59:59')
  
  // Use preview times if available, otherwise use original times
  const startTime = dragPreviewTimes.value ? dragPreviewTimes.value.start : new Date(entry.start_time)
  const endTime = dragPreviewTimes.value ? dragPreviewTimes.value.end : new Date(entry.end_time)
  
  // Calculate start percent based on whether the entry starts on this day
  const startPercent = startTime < dayStart 
    ? 0 
    : ((startTime.getHours() + startTime.getMinutes() / 60) / 24 * 100)
  
  // Calculate end percent based on whether the entry ends on this day
  const endPercent = endTime > dayEnd
    ? 100  // Fill to end of day
    : ((endTime.getHours() + endTime.getMinutes() / 60) / 24 * 100)
  
  const heightPercent = endPercent - startPercent
  
  // Get the project color or default to gray
  const color = entry.project?.color || '#E5E7EB'
  const rgb = hexToRgb(color)
  
  return {
    top: `${startPercent}%`,
    height: `${heightPercent}%`,
    backgroundColor: `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.15)`,
    borderColor: color,
  }
}

function getDurationForEntry(entry) {
  if (resizingEntry?.value?.id === entry.id && dragPreviewTimes.value) {
    // Calculate duration from preview times during resize
    const start = new Date(dragPreviewTimes.value.start)
    const end = new Date(dragPreviewTimes.value.end)
    return Math.round((end - start) / 1000)
  }
  return entry.duration
}
</script> 