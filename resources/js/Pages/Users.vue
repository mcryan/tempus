<!-- Users Management Page -->
<template>
  <Head title="Users" />
  <div>
    <!-- Header Section -->
    <div class="sm:flex sm:items-center sm:justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Users</h1>
        <p class="mt-2 text-sm text-gray-700">
          Manage team members, their roles, and billing rates.
        </p>
      </div>
      <div class="mt-4 sm:mt-0">
        <button
          @click="openCreateModal"
          class="inline-flex items-center justify-center rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
        >
          <PlusIcon class="h-5 w-5 mr-1.5" />
          New User
        </button>
      </div>
    </div>

    <!-- Stats Section -->
    <dl class="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-3">
      <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Total Users</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
          {{ users.length }}
        </dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Active Users</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
          {{ users.filter(u => u.projects?.some(p => p.is_active)).length }}
        </dd>
      </div>
      <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:p-6">
        <dt class="truncate text-sm font-medium text-gray-500">Admin Users</dt>
        <dd class="mt-1 text-3xl font-semibold tracking-tight text-gray-900">
          {{ users.filter(u => u.is_admin).length }}
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
            placeholder="Search users..."
            class="block w-full rounded-md border-0 py-1.5 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary-600 sm:text-sm sm:leading-6"
          />
          <div class="absolute inset-y-0 right-0 flex py-1.5 pr-1.5">
            <MagnifyingGlassIcon class="h-5 w-5 text-gray-400" />
          </div>
        </div>
      </div>
      <div class="mt-4 sm:mt-0 sm:ml-4">
        <select
          v-model="filterRole"
          class="block rounded-md border-0 py-1.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-primary-600 sm:text-sm sm:leading-6"
        >
          <option value="all">All Roles</option>
          <option value="admin">Admins</option>
          <option value="user">Regular Users</option>
        </select>
      </div>
    </div>

    <!-- Users Grid -->
    <div class="mt-8">
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div
          v-for="user in filteredUsers"
          :key="user.id"
          class="relative flex flex-col overflow-hidden rounded-lg bg-white shadow transition hover:shadow-md"
        >
          <div class="p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="relative">
                  <UserCircleIcon class="h-10 w-10 text-gray-400" />
                  <span
                    v-if="user.is_admin"
                    class="absolute -right-1 -top-1 h-4 w-4 rounded-full bg-primary-600 ring-2 ring-white"
                  >
                    <ShieldCheckIcon class="h-4 w-4 text-white" />
                  </span>
                </div>
              </div>
              <div class="ml-4 flex-1">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-medium text-gray-900">{{ user.name }}</h3>
                  <span
                    :class="[
                      'inline-flex items-center rounded-full px-2 py-1 text-xs font-medium',
                      user.is_admin
                        ? 'bg-primary-50 text-primary-700 ring-1 ring-inset ring-primary-600/20'
                        : 'bg-gray-50 text-gray-600 ring-1 ring-inset ring-gray-500/10'
                    ]"
                  >
                    {{ user.is_admin ? 'Admin' : 'User' }}
                  </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ user.email }}</p>
              </div>
            </div>

            <div class="mt-4">
              <div class="flex items-center text-sm text-gray-500">
                <CurrencyDollarIcon class="h-5 w-5 text-gray-400 mr-1.5" />
                <span>Billable Rate: {{ user.billable_rate ? `$${user.billable_rate}/hr` : 'Not set' }}</span>
              </div>
              <div class="mt-2 flex items-center text-sm text-gray-500">
                <FolderIcon class="h-5 w-5 text-gray-400 mr-1.5" />
                <span>{{ user.projects?.length || 0 }} projects</span>
              </div>
              <div class="mt-2 flex items-center text-sm text-gray-500">
                <ClockIcon class="h-5 w-5 text-gray-400 mr-1.5" />
                <span>{{ user.time_entries?.length || 0 }} time entries</span>
              </div>
            </div>
          </div>

          <div class="flex border-t border-gray-200 divide-x divide-gray-200">
            <button
              @click="editUser(user)"
              class="flex flex-1 items-center justify-center py-4 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
              <PencilSquareIcon class="h-4 w-4 mr-2" />
              Edit
            </button>
            <button
              v-if="!user.is_admin || currentUserId !== user.id"
              @click="deleteUser(user)"
              class="flex flex-1 items-center justify-center py-4 text-sm font-medium text-red-600 hover:bg-red-50"
            >
              <TrashIcon class="h-4 w-4 mr-2" />
              Delete
            </button>
          </div>
        </div>

        <!-- Empty State -->
        <div v-if="filteredUsers.length === 0" class="sm:col-span-2 lg:col-span-3">
          <div class="relative block w-full rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
            <UserGroupIcon class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-semibold text-gray-900">No users</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ users.length === 0 ? 'Get started by creating a new user.' : 'No users match your search.' }}
            </p>
            <div class="mt-6" v-if="users.length === 0">
              <button
                @click="openCreateModal"
                class="inline-flex items-center rounded-md bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-700"
              >
                <PlusIcon class="h-5 w-5 mr-1.5" />
                New User
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- User Modal -->
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
                {{ editingUser ? 'Edit User' : 'New User' }}
              </DialogTitle>

              <div v-if="errorMessage" class="mt-2 text-sm text-red-600">
                {{ errorMessage }}
              </div>

              <div class="mt-6 space-y-6">
                <div>
                  <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
                  <input
                    type="text"
                    id="name"
                    v-model="form.name"
                    :class="[
                      'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                      form.errors.name 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500'
                    ]"
                  />
                  <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                    {{ form.errors.name[0] }}
                  </p>
                </div>

                <div>
                  <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                  <input
                    type="email"
                    id="email"
                    v-model="form.email"
                    :class="[
                      'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                      form.errors.email 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500'
                    ]"
                  />
                  <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">
                    {{ form.errors.email[0] }}
                  </p>
                </div>

                <div>
                  <label for="password" class="block text-sm font-medium text-gray-700">
                    {{ editingUser ? 'New Password (leave blank to keep current)' : 'Password' }}
                  </label>
                  <input
                    type="password"
                    id="password"
                    v-model="form.password"
                    :required="!editingUser"
                    :class="[
                      'mt-1 block w-full rounded-md shadow-sm sm:text-sm',
                      form.errors.password 
                        ? 'border-red-300 focus:border-red-500 focus:ring-red-500'
                        : 'border-gray-300 focus:border-primary-500 focus:ring-primary-500'
                    ]"
                  />
                  <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">
                    {{ form.errors.password[0] }}
                  </p>
                </div>

                <div>
                  <label for="billable_rate" class="block text-sm font-medium text-gray-700">
                    Billable Rate ($/hr)
                  </label>
                  <input
                    type="number"
                    id="billable_rate"
                    v-model="form.billable_rate"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>

                <div>
                  <label for="pay_rate" class="block text-sm font-medium text-gray-700">
                    Pay Rate ($/hr)
                  </label>
                  <input
                    type="number"
                    id="pay_rate"
                    v-model="form.pay_rate"
                    step="0.01"
                    min="0"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm"
                  />
                </div>

                <div class="flex items-center">
                  <input
                    type="checkbox"
                    id="is_admin"
                    v-model="form.is_admin"
                    class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                  />
                  <label for="is_admin" class="ml-2 block text-sm text-gray-900">
                    Admin User
                  </label>
                </div>

                <div class="mt-6">
                  <label class="block text-sm font-medium text-gray-700">
                    Assigned Clients
                  </label>
                  <div class="mt-2 space-y-2 max-h-48 overflow-y-auto">
                    <div v-for="client in clients" :key="client.id" class="flex items-center">
                      <input
                        type="checkbox"
                        :id="'client-' + client.id"
                        v-model="selectedClients"
                        :value="client.id"
                        class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500"
                      />
                      <label :for="'client-' + client.id" class="ml-2 block text-sm text-gray-900">
                        {{ client.name }}
                      </label>
                    </div>
                    <div v-if="clients.length === 0" class="text-sm text-gray-500">
                      No clients available
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
                  @click="saveUser"
                  class="rounded-md border border-transparent bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700"
                >
                  {{ editingUser ? 'Save Changes' : 'Create User' }}
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
import { ref, computed, onMounted } from 'vue'
import { Head, usePage } from '@inertiajs/vue3'
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
  UserCircleIcon,
  UserGroupIcon,
  CurrencyDollarIcon,
  FolderIcon,
  ClockIcon,
  PencilSquareIcon,
  TrashIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/24/outline'
import axios from 'axios'

const users = ref([])
const showModal = ref(false)
const editingUser = ref(null)
const searchQuery = ref('')
const filterRole = ref('all')
const currentUserId = computed(() => usePage().props.auth?.user?.id)

const errors = ref({})
const errorMessage = ref('')

const form = ref({
  name: '',
  email: '',
  password: '',
  billable_rate: null,
  pay_rate: null,
  is_admin: false,
  errors: {}
})

const clients = ref([])
const selectedClients = ref([])

const filteredUsers = computed(() => {
  return users.value.filter(user => {
    const matchesSearch = user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
      user.email.toLowerCase().includes(searchQuery.value.toLowerCase())

    const matchesRole = filterRole.value === 'all' ||
      (filterRole.value === 'admin' && user.is_admin) ||
      (filterRole.value === 'user' && !user.is_admin)

    return matchesSearch && matchesRole
  })
})

async function loadUsers() {
  try {
    console.log('Loading users...')
    const response = await axios.get('/api/users')
    console.log('API Response:', response)
    users.value = response.data
    console.log('Users after loading:', users.value)
  } catch (error) {
    console.error('Error loading users:', error)
    if (error.response) {
      console.error('Error response:', error.response.data)
      console.error('Error status:', error.response.status)
    }
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

async function loadUserClients(userId) {
  try {
    const response = await axios.get(`/api/users/${userId}/clients`)
    selectedClients.value = response.data.map(client => client.id)
  } catch (error) {
    console.error('Error loading user clients:', error)
  }
}

function openCreateModal() {
  editingUser.value = null
  form.value = {
    name: '',
    email: '',
    password: '',
    billable_rate: null,
    pay_rate: null,
    is_admin: false,
    errors: {}
  }
  selectedClients.value = []
  showModal.value = true
}

function editUser(user) {
  editingUser.value = user
  form.value = {
    name: user.name,
    email: user.email,
    password: '',
    billable_rate: user.billable_rate,
    pay_rate: user.pay_rate,
    is_admin: user.is_admin,
    errors: {}
  }
  loadUserClients(user.id)
  showModal.value = true
}

async function saveUser() {
  try {
    form.value.errors = {}
    errorMessage.value = ''
    
    await axios.get('/sanctum/csrf-cookie')
    
    const data = { ...form.value }
    // Remove errors from data before sending
    delete data.errors
    
    if (editingUser.value && !data.password) {
      delete data.password
    }

    let userId;
    if (editingUser.value) {
      await axios.put(`/api/users/${editingUser.value.id}`, data)
      userId = editingUser.value.id
    } else {
      const response = await axios.post('/api/users', data)
      userId = response.data.id
    }

    // Only update clients if we have any selected
    if (selectedClients.value && selectedClients.value.length > 0) {
      try {
        await axios.put(`/api/users/${userId}/clients`, {
          client_ids: selectedClients.value
        })
      } catch (clientError) {
        console.error('Error updating user clients:', clientError)
        // Don't fail the whole operation if client assignment fails
      }
    }

    await loadUsers()
    closeModal()
  } catch (error) {
    console.error('Error saving user:', error)
    if (error.response?.data?.errors) {
      form.value.errors = error.response.data.errors
    }
    if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    }
  }
}

async function deleteUser(user) {
  if (user.id === currentUserId.value) {
    alert('You cannot delete your own account.')
    return
  }

  if (!confirm('Are you sure you want to delete this user? This will also delete all their projects and time entries.')) {
    return
  }

  try {
    await axios.delete(`/api/users/${user.id}`)
    await loadUsers()
  } catch (error) {
    console.error('Error deleting user:', error)
  }
}

function closeModal() {
  showModal.value = false
  form.value.errors = {}
  errorMessage.value = ''
}

onMounted(() => {
  loadUsers()
  loadClients()
})
</script> 