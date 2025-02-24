import { createRouter, createWebHistory } from 'vue-router'
import TimeTracker from './components/TimeTracker.vue'
import Projects from './pages/Projects.vue'
import Tags from './pages/Tags.vue'
import Clients from './pages/Clients.vue'
import Users from './pages/Users.vue'
import Login from './pages/Auth/Login.vue'
import AppLayout from './components/AppLayout.vue'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: Login,
    meta: { requiresAuth: false }
  },
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/timer'
      },
      {
        path: 'timer',
        name: 'timer',
        component: TimeTracker
      },
      {
        path: 'projects',
        name: 'projects',
        component: Projects
      },
      {
        path: 'tags',
        name: 'tags',
        component: Tags
      },
      {
        path: 'clients',
        name: 'clients',
        component: Clients,
        meta: { requiresAdmin: true }
      },
      {
        path: 'users',
        name: 'users',
        component: Users,
        meta: { requiresAdmin: true }
      }
    ]
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// Navigation guard
router.beforeEach(async (to, from, next) => {
  // Check if the route requires auth
  if (to.matched.some(record => record.meta.requiresAuth)) {
    try {
      // Make a request to check auth status
      const response = await fetch('/api/user', {
        credentials: 'include'
      })
      
      if (!response.ok) {
        throw new Error('Not authenticated')
      }

      const user = await response.json()

      // Check if route requires admin access
      if (to.matched.some(record => record.meta.requiresAdmin) && !user.is_admin) {
        // If user is not admin, redirect to timer page
        next('/timer')
        return
      }

      next()
    } catch (error) {
      next('/login')
    }
  } else {
    next()
  }
})

export default router 