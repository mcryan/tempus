<template>
  <Head title="Projects" />
  <div>
    <!-- Header Section -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
        <p class="mt-2 text-sm text-gray-700">
          Manage your projects, track time, and organize client work.
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
        >
          <PlusIcon class="h-5 w-5 mr-1.5" />
          New Project
        </button>
      </div>
    </div>

    <!-- Stats Section -->
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
      <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Active Projects</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
          {{ projects.filter(p => p.is_active).length }}
        </dd>
      </div>
      <div v-if="isAdmin" class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Billable Projects</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
          {{ projects.filter(p => p.is_billable).length }}
        </dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Total Projects</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
          {{ projects.length }}
        </dd>
      </div>
    </dl>

    <!-- Filters Section -->
    <div class="mt-8 sm:flex sm:items-center">
      <div class="sm:flex-auto">
        <div class="relative mt-2 max-w-lg">
          <input
            type="text"
            v-model="searchQuery"
            placeholder="Search projects..."
            class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
          />
          <div class="absolute inset-y-0 right-0 flex py-1.5 pr-1.5">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
          </div>
        </div>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-4">
        <select
          v-model="filterStatus"
          class="block rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
        >
          <option value="all">All Status</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
        </select>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-4">
        <select
          v-model="filterClient"
          class="block rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
        >
          <option value="all">All Clients</option>
          <option v-for="client in uniqueClients" :key="client.id" :value="client.id">
            {{ client.name }}
          </option>
        </select>
      </div>
    </div>

    <!-- Projects Table -->
    <div class="mt-8 flow-root">
      <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
        <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
          <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-300">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900 sm:pl-6">
                    Name
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    Client
                  </th>
                  <th v-if="isAdmin" scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    Rate
                  </th>
                  <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">
                    Status
                  </th>
                  <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6">
                    <span class="sr-only">Actions</span>
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-for="project in filteredProjects" :key="project.id" class="hover:bg-gray-50">
                  <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-6">
                    <div class="flex items-center">
                      <div
                        class="h-4 w-4 flex-shrink-0 rounded-full"
                        :style="{ backgroundColor: project.color }"
                      ></div>
                      <div class="ml-4">
                        <div class="font-medium text-gray-900">{{ project.name }}</div>
                        <div class="text-gray-500">{{ project.description || 'No description' }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <div class="flex items-center">
                      <UserCircleIcon v-if="!project.client" class="h-5 w-5 text-gray-400 mr-1.5" />
                      <BuildingOfficeIcon v-else class="h-5 w-5 text-gray-400 mr-1.5" />
                      {{ project.client?.name || 'No Client' }}
                    </div>
                  </td>
                  <td v-if="isAdmin" class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <div class="flex items-center">
                      <CurrencyDollarIcon class="h-5 w-5 text-gray-400 mr-1.5" />
                      {{ project.billable_rate ? `${project.billable_rate}/hr` : 'Not set' }}
                    </div>
                  </td>
                  <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-500">
                    <span
                      :class="[
                        'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                        project.is_active
                          ? 'bg-green-50 text-green-700 ring-green-600/20'
                          : 'bg-red-50 text-red-700 ring-red-600/20'
                      ]"
                    >
                      <div
                        :class="[
                          'mr-1 h-1.5 w-1.5 rounded-full',
                          project.is_active ? 'bg-green-400' : 'bg-red-400'
                        ]"
                      />
                      {{ project.is_active ? 'Active' : 'Inactive' }}
                    </span>
                  </td>
                  <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6">
                    <div class="flex justify-end space-x-3">
                      <button
                        @click="editProject(project)"
                        class="inline-flex items-center rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                      >
                        <PencilSquareIcon class="h-4 w-4 mr-1" />
                        Edit
                      </button>
                      <button
                        @click="deleteProject(project)"
                        class="inline-flex items-center rounded-md bg-red-50 px-2.5 py-1.5 text-sm font-semibold text-red-700 shadow-sm ring-1 ring-inset ring-red-600/20 hover:bg-red-100"
                      >
                        <TrashIcon class="h-4 w-4 mr-1" />
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="filteredProjects.length === 0">
                  <td colspan="5" class="py-12">
                    <div class="text-center">
                      <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
                      <h3 class="mt-2 text-sm font-semibold text-gray-900">No projects</h3>
                      <p class="mt-1 text-sm text-gray-500">
                        {{ projects.length === 0 ? 'Get started by creating a new project.' : 'No projects match your filters.' }}
                      </p>
                      <div class="mt-6" v-if="projects.length === 0">
                        <button
                          @click="openCreateModal"
                          class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
                        >
                          <PlusIcon class="h-5 w-5 mr-1.5" />
                          New Project
                        </button>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Project Modal -->
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
                {{ isEditing ? 'Edit Project' : 'New Project' }}
              </DialogTitle>

              <div class="mt-4">
                <div class="space-y-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Name
                    </label>
                    <input
                      type="text"
                      v-model="form.name"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Client
                    </label>
                    <select
                      v-model="form.client_id"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                      <option :value="null">No Client</option>
                      <option
                        v-for="client in clients"
                        :key="client.id"
                        :value="client.id"
                      >
                        {{ client.name }}
                      </option>
                    </select>
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Color
                    </label>
                    <input
                      type="color"
                      v-model="form.color"
                      class="mt-1 block w-full h-10 rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                  </div>

                  <div v-if="isAdmin">
                    <label class="block text-sm font-medium text-gray-700">
                      Billable Rate ($/hr)
                    </label>
                    <input
                      type="number"
                      step="0.01"
                      v-model="form.billable_rate"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                  </div>

                  <div>
                    <label class="block text-sm font-medium text-gray-700">
                      Description
                    </label>
                    <textarea
                      v-model="form.description"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    ></textarea>
                  </div>

                  <div v-if="isAdmin" class="flex items-center space-x-4">
                    <div class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="form.is_billable"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      >
                      <label class="ml-2 block text-sm text-gray-900">
                        Billable
                      </label>
                    </div>

                    <div class="flex items-center">
                      <input
                        type="checkbox"
                        v-model="form.is_active"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      >
                      <label class="ml-2 block text-sm text-gray-900">
                        Active
                      </label>
                    </div>
                  </div>
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
                  @click="saveProject"
                  class="rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
                >
                  {{ isEditing ? 'Save Changes' : 'Create Project' }}
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import axios from 'axios'
import { Head, Link, usePage } from '@inertiajs/vue3'
import {
  PlusIcon,
  MagnifyingGlassIcon,
  UserCircleIcon,
  BuildingOfficeIcon,
  CurrencyDollarIcon,
  PencilSquareIcon,
  TrashIcon,
  FolderIcon
} from '@heroicons/vue/24/outline'

const projects = ref([])
const clients = ref([])
const showModal = ref(false)
const isEditing = ref(false)
const form = ref({
  name: '',
  client_id: null,
  color: '#4F46E5',
  description: '',
  is_active: true,
  is_billable: true,
  billable_rate: null,
})
const editingId = ref(null)

const searchQuery = ref('')
const filterStatus = ref('all')
const filterClient = ref('all')

const isAdmin = computed(() => usePage().props.auth?.user?.is_admin)

async function loadProjects() {
  try {
    const response = await axios.get('/api/projects')
    projects.value = response.data
  } catch (error) {
    console.error('Error loading projects:', error)
  }
}

async function loadClients() {
  try {
    const response = await axios.get('/api/clients')
    clients.value = response.data
  } catch (error) {
    console.error('Error loading clients:', error)
  }
}

function openCreateModal() {
  isEditing.value = false
  editingId.value = null
  form.value = {
    name: '',
    client_id: null,
    color: '#4F46E5',
    description: '',
    is_active: true,
    is_billable: true,
    billable_rate: null,
  }
  showModal.value = true
}

function editProject(project) {
  isEditing.value = true
  editingId.value = project.id
  form.value = {
    name: project.name,
    client_id: project.client_id,
    color: project.color,
    description: project.description,
    is_active: project.is_active,
    is_billable: project.is_billable,
    billable_rate: project.billable_rate,
  }
  showModal.value = true
}

async function saveProject() {
  try {
    // Get CSRF cookie first
    await axios.get('/sanctum/csrf-cookie')
    
    if (isEditing.value) {
      await axios.put(`/api/projects/${editingId.value}`, form.value)
    } else {
      await axios.post('/api/projects', form.value)
    }
    await loadProjects()
    closeModal()
  } catch (error) {
    console.error('Error saving project:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Error status:', error.response.status)
    }
  }
}

async function deleteProject(project) {
  if (!confirm('Are you sure you want to delete this project?')) {
    return
  }

  try {
    await axios.delete(`/api/projects/${project.id}`)
    await loadProjects()
  } catch (error) {
    console.error('Error deleting project:', error)
  }
}

function closeModal() {
  showModal.value = false
}

// Watch for client changes to update billable settings
watch(() => form.value.client_id, (newClientId) => {
  if (newClientId) {
    const selectedClient = clients.value.find(c => c.id === newClientId)
    if (selectedClient) {
      form.value.is_billable = selectedClient.is_billable_by_default
      form.value.billable_rate = form.value.billable_rate || selectedClient.default_billable_rate
    }
  }
})

const uniqueClients = computed(() => {
  const clients = projects.value
    .map(p => p.client)
    .filter(c => c)
    .reduce((acc, client) => {
      if (!acc.find(c => c.id === client.id)) {
        acc.push(client)
      }
      return acc
    }, [])
  return clients.sort((a, b) => a.name.localeCompare(b.name))
})

const filteredProjects = computed(() => {
  return projects.value.filter(project => {
    const matchesSearch = project.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      project.description?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      project.client?.name.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesStatus = filterStatus.value === 'all' ||
      (filterStatus.value === 'active' && project.is_active) ||
      (filterStatus.value === 'inactive' && !project.is_active)

    const matchesClient = filterClient.value === 'all' ||
      project.client?.id === parseInt(filterClient.value)

    return matchesSearch && matchesStatus && matchesClient
  })
})

onMounted(() => {
  loadProjects()
  loadClients()
})
</script> 