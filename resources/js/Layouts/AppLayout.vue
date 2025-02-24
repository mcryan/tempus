<!-- Layout with top navigation -->
<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-200">
      <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex">
            <div class="flex-shrink-0 flex items-center">
              <h1 class="text-xl font-bold text-primary-600">Tempus</h1>
            </div>
            <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
              <Link
                href="/timer"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="[
                  currentPath.startsWith('/timer')
                    ? 'border-primary-500 text-gray-900'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                <ClockIcon class="h-5 w-5 mr-1" />
                Timer
              </Link>

              <Link
                href="/projects"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="[
                  currentPath.startsWith('/projects')
                    ? 'border-primary-500 text-gray-900'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                <FolderIcon class="h-5 w-5 mr-1" />
                Projects
              </Link>

              <Link
                href="/tags"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="[
                  currentPath.startsWith('/tags')
                    ? 'border-primary-500 text-gray-900'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                <TagIcon class="h-5 w-5 mr-1" />
                Tags
              </Link>

              <Link
                v-if="isAdmin"
                href="/clients"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="[
                  currentPath.startsWith('/clients')
                    ? 'border-primary-500 text-gray-900'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                <BuildingOfficeIcon class="h-5 w-5 mr-1" />
                Clients
              </Link>

              <Link
                v-if="isAdmin"
                href="/users"
                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
                :class="[
                  currentPath.startsWith('/users')
                    ? 'border-primary-500 text-gray-900'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'
                ]"
              >
                <UserGroupIcon class="h-5 w-5 mr-1" />
                Users
              </Link>
            </div>
          </div>

          <!-- User Menu -->
          <div v-if="userName" class="flex items-center space-x-4">
            <span class="text-sm font-medium text-gray-900">{{ userName }}</span>
            <Link
              href="/logout"
              method="post"
              as="button"
              class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              <ArrowRightOnRectangleIcon class="h-5 w-5 mr-1" />
              Logout
            </Link>
          </div>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="py-10">
      <div class="w-full px-4 sm:px-6 lg:px-8">
        <slot></slot>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3'
import { computed, onMounted } from 'vue'
import {
  ClockIcon,
  FolderIcon,
  TagIcon,
  BuildingOfficeIcon,
  UserGroupIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/vue/24/outline'

const page = usePage()
const currentPath = computed(() => window.location.pathname)
const userName = computed(() => usePage().props.auth?.user?.name)
const isAdmin = computed(() => usePage().props.auth?.user?.is_admin)

onMounted(() => {
  const pageProps = usePage().props
  console.log('Page props:', pageProps)
  console.log('Auth data:', pageProps.auth)
  console.log('User data:', pageProps.auth?.user)
})
</script> 