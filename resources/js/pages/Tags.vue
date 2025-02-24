<template>
  <Head title="Tags" />
  <AppLayout>
    <div>
      <!-- Header Section -->
      <div class="sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Tags</h1>
          <p class="mt-2 text-sm text-gray-700">
            Organize and categorize your time entries with tags.
          </p>
        </div>
        <div class="mt-4 sm:mt-0">
          <button
            @click="openCreateModal"
            class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
          >
            <PlusIcon class="h-5 w-5 mr-1.5" />
            New Tag
          </button>
        </div>
      </div>

      <!-- Stats Section -->
      <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Total Tags</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            {{ tags.length }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Tagged Time Entries</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            {{ tags.reduce((sum, tag) => sum + (tag.time_entries?.length || 0), 0) }}
          </dd>
        </div>
      </dl>

      <!-- Search Section -->
      <div class="mt-8">
        <div class="relative max-w-lg">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search tags..."
            class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
          />
          <div class="absolute inset-y-0 right-0 flex py-1.5 pr-1.5">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
          </div>
        </div>
      </div>

      <!-- Tags Grid -->
      <div class="mt-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="tag in filteredTags"
            :key="tag.id"
            class="relative flex items-center space-x-3 rounded-lg border border-gray-300 bg-white px-6 py-5 shadow-sm hover:border-gray-400"
          >
            <div class="flex-shrink-0">
              <div
                class="h-8 w-8 rounded-full"
                :style="{ backgroundColor: tag.color }"
              ></div>
            </div>
            <div class="min-w-0 flex-1">
              <span class="absolute inset-0" aria-hidden="true" />
              <p class="text-sm font-medium text-gray-900">{{ tag.name }}</p>
              <p class="truncate text-sm text-gray-500">
                {{ tag.time_entries?.length || 0 }} time entries
              </p>
            </div>
            <div class="flex-shrink-0 space-x-2">
              <button
                @click.stop="editTag(tag)"
                class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
              >
                <PencilSquareIcon class="h-4 w-4" />
                <span class="sr-only">Edit</span>
              </button>
              <button
                @click.stop="deleteTag(tag)"
                class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-600/20 hover:bg-red-100"
              >
                <TrashIcon class="h-4 w-4" />
                <span class="sr-only">Delete</span>
              </button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredTags.length === 0" class="sm:col-span-2 lg:col-span-3">
            <div class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
              <TagIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3 class="mt-2 text-sm font-semibold text-gray-900">No tags</h3>
              <p class="mt-1 text-sm text-gray-500">
                {{ tags.length === 0 ? 'Get started by creating a new tag.' : 'No tags match your search.' }}
              </p>
              <div class="mt-6" v-if="tags.length === 0">
                <button
                  @click="openCreateModal"
                  class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
                >
                  <PlusIcon class="h-5 w-5 mr-1.5" />
                  New Tag
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Tag Modal -->
    <TransitionRoot appear :show="showModal" as="template">
      <Dialog as="div" @close="closeModal" class="relative z-10">
        <TransitionChild
          as="template"
          enter="duration-300 ease-out"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="duration-200 ease-in"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-black bg-opacity-25" />
        </TransitionChild>

        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <TransitionChild
              as="template"
              enter="duration-300 ease-out"
              enter-from="opacity-0 scale-95"
              enter-to="opacity-100 scale-100"
              leave="duration-200 ease-in"
              leave-from="opacity-100 scale-100"
              leave-to="opacity-0 scale-95"
            >
              <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                  {{ editingTag ? 'Edit Tag' : 'Create Tag' }}
                </DialogTitle>

                <div class="mt-6 space-y-6">
                  <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input
                      type="text"
                      id="name"
                      v-model="form.name"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label for="color" class="block text-sm font-medium text-gray-700">Color</label>
                    <input
                      type="color"
                      id="color"
                      v-model="form.color"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm h-10"
                    />
                  </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                  <button
                    @click="closeModal"
                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                  >
                    Cancel
                  </button>
                  <button
                    @click="saveTag"
                    class="rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
                  >
                    {{ editingTag ? 'Save Changes' : 'Create Tag' }}
                  </button>
                </div>
              </DialogPanel>
            </TransitionChild>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  TagIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const tags = ref([])
const showModal = ref(false)
const editingTag = ref(null)
const searchQuery = ref('')

const form = ref({
  name: '',
  color: '#4F46E5',
})

const filteredTags = computed(() => {
  if (!searchQuery.value) return tags.value
  const query = searchQuery.value.toLowerCase()
  return tags.value.filter(tag => 
    tag.name.toLowerCase().includes(query)
  )
})

async function loadTags() {
  try {
    const response = await axios.get('/api/tags')
    tags.value = response.data
  } catch (error) {
    console.error('Error loading tags:', error)
  }
}

function openCreateModal() {
  editingTag.value = null
  form.value = {
    name: '',
    color: '#4F46E5',
  }
  showModal.value = true
}

function editTag(tag) {
  editingTag.value = tag
  form.value = {
    name: tag.name,
    color: tag.color,
  }
  showModal.value = true
}

async function saveTag() {
  try {
    if (editingTag.value) {
      await axios.put(`/api/tags/${editingTag.value.id}`, form.value)
    } else {
      await axios.post('/api/tags', form.value)
    }
    await loadTags()
    closeModal()
  } catch (error) {
    console.error('Error saving tag:', error)
  }
}

async function deleteTag(tag) {
  if (!confirm('Are you sure you want to delete this tag?')) {
    return
  }

  try {
    await axios.delete(`/api/tags/${tag.id}`)
    await loadTags()
  } catch (error) {
    console.error('Error deleting tag:', error)
  }
}

function closeModal() {
  showModal.value = false
}

onMounted(() => {
  loadTags()
})
</script> 