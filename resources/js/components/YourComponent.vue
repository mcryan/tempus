import { useProjects } from '@/composables/useProjects'

export default {
    setup() {
        const { projects, isLoading, fetchProjects } = useProjects()

        // Fetch only once when component mounts
        onMounted(() => {
            fetchProjects()
        })

        return {
            projects,
            isLoading
        }
    }
} 