import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js';
import {Ziggy} from './ziggy';
import '../css/app.css';

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({
            render: () => h(App, {
                ...props,
                key: props.initialPage.props.auth.user ? 'authenticated' : 'guest'
            })
        })
            .use(plugin)
            .use(ZiggyVue,Ziggy)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
