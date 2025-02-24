<!-- Clients Management Page -->
<template>
  <Head title="Clients" />
  <AppLayout>
    <div>
      <!-- Header Section -->
      <div class="sm:flex sm:items-center sm:justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Clients</h1>
          <p class="mt-2 text-sm text-gray-700">
            Manage your clients and their associated projects.
          </p>
        </div>
        <div class="mt-4 sm:mt-0">
          <button
            @click="openCreateModal"
            class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
          >
            <PlusIcon class="h-5 w-5 mr-1.5" />
            New Client
          </button>
        </div>
      </div>

      <!-- Stats Section -->
      <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Total Clients</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            {{ clients.length }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Active Projects</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            {{ totalActiveProjects }}
          </dd>
        </div>
        <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
          <dt class="truncate text-sm font-medium text-gray-500">Total Billable Amount</dt>
          <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
            ${{ totalBillableAmount.toFixed(2) }}
          </dd>
        </div>
      </dl>

      <!-- Search and Filter Section -->
      <div class="mt-8 sm:flex sm:items-center">
        <div class="sm:flex-auto">
          <div class="relative mt-2 max-w-lg">
            <input
              type="text"
              v-model="searchQuery"
              placeholder="Search clients..."
              class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
            />
            <div class="absolute inset-y-0 right-0 flex py-1.5 pr-1.5">
              <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
            </div>
          </div>
        </div>
        <div class="mt-4 sm:mt-0 sm:ml-4">
          <select
            v-model="filterBillable"
            class="block rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
          >
            <option value="all">All Clients</option>
            <option value="billable">Billable</option>
            <option value="non-billable">Non-billable</option>
          </select>
        </div>
      </div>

      <!-- Clients Grid -->
      <div class="mt-8">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="client in filteredClients"
            :key="client.id"
            class="relative flex flex-col overflow-hidden rounded-lg bg-white shadow transition hover:shadow-md"
          >
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <BuildingOfficeIcon class="h-8 w-8 text-gray-400" />
                </div>
                <div class="ml-4 flex-1">
                  <h3 class="text-lg font-medium text-gray-900">{{ client.name }}</h3>
                  <p class="mt-1 text-sm text-gray-500">
                    {{ client.projects?.length || 0 }} projects
                  </p>
                </div>
              </div>

              <div class="mt-4">
                <div class="flex items-center text-sm text-gray-500">
                  <CurrencyDollarIcon class="h-5 w-5 text-gray-400 mr-1.5" />
                  <span>Default Rate: {{ client.default_billable_rate ? `$${client.default_billable_rate}/hr` : 'Not set' }}</span>
                </div>
                <div class="mt-2 flex items-center text-sm text-gray-500">
                  <ClipboardDocumentCheckIcon class="h-5 w-5 text-gray-400 mr-1.5" />
                  <span>{{ client.is_billable_by_default ? 'Billable by default' : 'Non-billable by default' }}</span>
                </div>
              </div>

              <div class="mt-4">
                <p class="text-sm text-gray-500">
                  {{ client.notes || 'No notes available' }}
                </p>
              </div>
            </div>

            <div class="flex border-t border-gray-200 divide-x divide-gray-200">
              <button
                @click="editClient(client)"
                class="flex flex-1 items-center justify-center py-4 text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                <PencilSquareIcon class="h-4 w-4 mr-2" />
                Edit
              </button>
              <button
                @click="deleteClient(client)"
                class="flex flex-1 items-center justify-center py-4 text-sm font-medium text-red-600 hover:bg-red-50"
              >
                <TrashIcon class="h-4 w-4 mr-2" />
                Delete
              </button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="filteredClients.length === 0" class="sm:col-span-2 lg:col-span-3">
            <div class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
              <BuildingOfficeIcon class="mx-auto h-12 w-12 text-gray-400" />
              <h3 class="mt-2 text-sm font-semibold text-gray-900">No clients</h3>
              <p class="mt-1 text-sm text-gray-500">
                {{ clients.length === 0 ? 'Get started by creating a new client.' : 'No clients match your search.' }}
              </p>
              <div class="mt-6" v-if="clients.length === 0">
                <button
                  @click="openCreateModal"
                  class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
                >
                  <PlusIcon class="h-5 w-5 mr-1.5" />
                  New Client
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Client Modal -->
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
                  {{ editingClient ? 'Edit Client' : 'New Client' }}
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
                    <label for="default_billable_rate" class="block text-sm font-medium text-gray-700">
                      Default Billable Rate ($/hr)
                    </label>
                    <input
                      type="number"
                      id="default_billable_rate"
                      v-model="form.default_billable_rate"
                      step="0.01"
                      min="0"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    />
                  </div>

                  <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                    <textarea
                      id="notes"
                      v-model="form.notes"
                      rows="3"
                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                    ></textarea>
                  </div>

                  <div class="flex items-center">
                    <input
                      type="checkbox"
                      id="is_billable_by_default"
                      v-model="form.is_billable_by_default"
                      class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                    />
                    <label for="is_billable_by_default" class="ml-2 block text-sm text-gray-900">
                      Billable by default
                    </label>
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
                    @click="saveClient"
                    class="rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
                  >
                    {{ editingClient ? 'Save Changes' : 'Create Client' }}
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
  BuildingOfficeIcon,
  CurrencyDollarIcon,
  ClipboardDocumentCheckIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const clients = ref([])
const showModal = ref(false)
const editingClient = ref(null)
const searchQuery = ref('')
const filterBillable = ref('all')

const form = ref({
  name: '',
  default_billable_rate: null,
  is_billable_by_default: true,
  notes: '',
})

const filteredClients = computed(() => {
  return clients.value.filter(client => {
    const matchesSearch = client.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      client.notes?.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesBillable = filterBillable.value === 'all' ||
      (filterBillable.value === 'billable' && client.is_billable_by_default) ||
      (filterBillable.value === 'non-billable' && !client.is_billable_by_default)

    return matchesSearch && matchesBillable
  })
})

const totalActiveProjects = computed(() => {
  return clients.value.reduce((sum, client) => {
    return sum + (client.projects?.filter(p => p.is_active)?.length || 0)
  }, 0)
})

const totalBillableAmount = computed(() => {
  return clients.value.reduce((sum, client) => {
    const clientProjects = client.projects || []
    return sum + clientProjects.reduce((projectSum, project) => {
      return projectSum + (Number(project.billable_rate) || 0)
    }, 0)
  }, 0)
})

async function loadClients() {
  try {
    console.log('Loading clients...')
    const response = await axios.get('/api/clients')
    console.log('API Response:', response)
    clients.value = response.data
    console.log('Clients after loading:', clients.value)
  } catch (error) {
    console.error('Error loading clients:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Error status:', error.response.status)
    }
  }
}

function openCreateModal() {
  editingClient.value = null
  form.value = {
    name: '',
    default_billable_rate: null,
    is_billable_by_default: true,
    notes: '',
  }
  showModal.value = true
}

function editClient(client) {
  editingClient.value = client
  form.value = {
    name: client.name,
    default_billable_rate: client.default_billable_rate,
    is_billable_by_default: client.is_billable_by_default,
    notes: client.notes,
  }
  showModal.value = true
}

async function saveClient() {
  try {
    if (editingClient.value) {
      await axios.put(`/api/clients/${editingClient.value.id}`, form.value)
    } else {
      await axios.post('/api/clients', form.value)
    }
    await loadClients()
    closeModal()
  } catch (error) {
    console.error('Error saving client:', error)
  }
}

async function deleteClient(client) {
  if (!confirm('Are you sure you want to delete this client? This will not delete associated projects.')) {
    return
  }

  try {
    await axios.delete(`/api/clients/${client.id}`)
    await loadClients()
  } catch (error) {
    console.error('Error deleting client:', error)
  }
}

function closeModal() {
  showModal.value = false
}

onMounted(() => {
  loadClients()
})
</script> 