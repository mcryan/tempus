import { ref } from 'vue'
import axios from 'axios'

// Move fetchPromise outside the function to make it truly shared
let fetchPromise = null

export function useProjects() {
    const projects = ref([])
    const isLoading = ref(false)
    const error = ref(null)

    const fetchProjects = async () => {
        // Add debugging
        console.log('fetchProjects called')
        
        // If we already have projects loaded, return them
        if (projects.value.length > 0) {
            console.log('Returning cached projects:', projects.value)
            return projects.value
        }

        // If we're already fetching, return the existing promise
        if (fetchPromise) {
            console.log('Using existing fetch promise')
            const response = await fetchPromise
            projects.value = response.data
            return projects.value
        }

        isLoading.value = true
        error.value = null

        try {
            console.log('Making API request to /api/projects')
            // Get CSRF cookie before making the request
            await axios.get('/sanctum/csrf-cookie')
            
            // Create the promise for the request
            fetchPromise = axios.get('/api/projects')
            
            // Wait for the response
            const response = await fetchPromise
            console.log('API response:', response.data)
            projects.value = response.data
            return projects.value
        } catch (e) {
            console.error('Error fetching projects:', e)
            error.value = e
            throw e
        } finally {
            isLoading.value = false
            // Clear the promise after a short delay to prevent race conditions
            setTimeout(() => {
                fetchPromise = null
            }, 100)
        }
    }

    return {
        projects,
        isLoading,
        error,
        fetchProjects
    }
} 