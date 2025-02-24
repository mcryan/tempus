import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        let page = pages[`./Pages/${name}.vue`]
        page.default.layout = page.default.layout || AppLayout
        return page
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
        app.use(plugin)
        return app.mount(el)
    },
    progress: {
        color: '#4B5563',
    },
    title: title => `${title} - Tempus`
}).catch(e => console.error(e));

// Add this where you set up axios
axios.interceptors.request.use(function (config) {
    if (config.url === '/projects') {
        console.trace('Projects request made');
    }
    return config;
}); 