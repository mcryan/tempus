<template>
  <!-- Show loading state -->
  <div v-if="loading" class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="text-center">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
      <p class="mt-4 text-gray-600">Loading...</p>
    </div>
  </div>

  <!-- Show login when not authenticated -->
  <Login v-else-if="!isAuthenticated" @authenticated="checkAuth" />
  
  <!-- Show main app when authenticated -->
  <AppLayout v-else :user="user" @logout="handleLogout">
    <router-view></router-view>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from './components/AppLayout.vue'
import Login from './components/Login.vue'
import axios from 'axios'

const router = useRouter()
const user = ref(null)
const isAuthenticated = ref(false)
const loading = ref(true)

async function checkAuth() {
  try {
    loading.value = true
    const response = await axios.get('/api/user')
    console.log('User data from API:', response.data)
    user.value = response.data
    isAuthenticated.value = true
  } catch (error) {
    console.error('Auth check failed:', error)
    isAuthenticated.value = false
    user.value = null
    router.push('/')
  } finally {
    loading.value = false
  }
}

async function handleLogout() {
  try {
    loading.value = true
    // First invalidate the API token
    await axios.post('/api/logout')
    
    // Then clear the session cookie
    document.cookie = 'XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    document.cookie = 'laravel_session=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;'
    
    // Clear user data and authentication state
    isAuthenticated.value = false
    user.value = null
    
    // Redirect to home/login
    router.push('/')
  } catch (error) {
    console.error('Failed to logout:', error)
  } finally {
    loading.value = false
  }
}

// Watch route changes
watch(() => router.currentRoute.value, (route) => {
  if (!isAuthenticated.value && route.meta.requiresAuth) {
    router.push('/')
  }
}, { immediate: true, deep: true })

onMounted(() => {
  checkAuth()
})
</script>