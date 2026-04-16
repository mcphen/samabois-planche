<template>
    <div id="wrapper" class="theme-cyan">
        <transition name="page-loader">
            <div v-if="isPageLoading" class="page-loader-overlay">
                <div class="page-loader-card">
                    <div class="page-loader-spinner"></div>
                    <p class="page-loader-text">Chargement en cours...</p>
                </div>
            </div>
        </transition>

        <nav class="navbar navbar-fixed-top">
            <div class="container-fluid">
                <nav class="navbar-fixed-top">
                    <div class="container-fluid content_change">
                        <div class="navbar-brand">
                            <button type="button" class="btn-toggle-offcanvas" @click="toggleSidebar"><i class="fa fa-bars"></i></button>
                            <button type="button" class="btn-toggle-fullwidth" @click="toggleSidebar"><i class="fa fa-bars"></i></button>
                            <Link href="/" class="text-blue-500 hover:underline">
                                {{ appName }}
                            </Link>
                        </div>

                        <div class="navbar-right">
                            <div id="navbar-menu">
                                <ul class="nav navbar-nav">
                                    <li>
                                        <button type="button" class="icon-menu" @click="logout">
                                            <i class="fa fa-power-off"></i>
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>
            </div>
        </nav>

        <div id="left-sidebar" class="sidebar" :class="{ active: sidebarVisible }">
            <button type="button" class="btn-toggle-offcanvas">
                <i class="fa fa-arrow-left"></i>
            </button>

            <div class="sidebar-scroll">
                <div class="user-account">
                    <img :src="'/assets/images/user.png'" class="rounded-circle user-photo" alt="User Profile Picture">
                    <div class="dropdown">
                        <strong>
                            <span>{{ authUser?.name }}</span>
                        </strong>
                        <br>
                        <span class="badge" :class="authUser?.role === 'admin' ? 'badge-danger' : 'badge-info'" style="font-size:11px;">
                            {{ authUser?.role === 'admin' ? 'Admin' : 'Comptable' }}
                        </span>
                    </div>
                </div>

                <div class="tab-content padding-0">
                    <div class="tab-pane active" id="menu">
                        <nav id="left-sidebar-nav" class="sidebar-nav">
                            <ul id="main-menu" class="metismenu li_animation_delay">
                                <li v-for="item in menuItems" :key="item.name" :class="{ active: currentUrl.includes(item.url) }">
                                    <template v-if="item.subMenu && item.subMenu.length > 0">
                                        <a
                                            href="#"
                                            class="has-arrow"
                                            :aria-expanded="activeSubMenu === item.name ? 'true' : 'false'"
                                            @click.prevent="toggleSubMenu(item.name)"
                                        >
                                            <i :class="item.icon"></i> <span>{{ item.name }}</span>
                                        </a>
                                        <ul v-show="activeSubMenu === item.name" :aria-expanded="activeSubMenu === item.name ? 'true' : 'false'">
                                            <li v-for="sub in item.subMenu" :key="sub.name" :class="{ active: currentUrl.includes(sub.url) }">
                                                <Link :href="sub.url">{{ sub.name }}</Link>
                                            </li>
                                        </ul>
                                    </template>
                                    <template v-else>
                                        <Link :href="item.url">
                                            <i :class="item.icon"></i> <span>{{ item.name }}</span>
                                        </Link>
                                    </template>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <div id="main-content" :class="containerClasses">
            <div>
                <slot />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const currentUrl = window.location.pathname;
const page = usePage();
const appName = import.meta.env.VITE_APP_NAME || page.props?.appName || 'SAMABOIS';
const authUser = computed(() => page.props.auth?.user);
const isAdmin = computed(() => authUser.value?.role === 'admin');
const isPageLoading = ref(true);
const sidebarVisible = ref(window.innerWidth >= 768);
const activeSubMenu = ref(null);

let removeInertiaStartListener = null;
let removeInertiaFinishListener = null;
let removeInertiaErrorListener = null;
let removeWindowLoadListener = null;

const containerClasses = computed(() => ({
    'container-fluid': !sidebarVisible.value,
    'full-width': sidebarVisible.value,
}));

// Menu complet — filtré selon le rôle au rendu
const allMenuItems = [
    {
        name: 'Tableau de bord',
        icon: 'fa fa-dashboard',
        url: '/dashboard',
        roles: ['admin'],
        subMenu: [],
    },
    {
        name: 'Planches',
        icon: 'fa fa-th-large',
        roles: ['admin', 'comptable'],
        subMenu: [
            { name: 'Liste des planches',   url: '/admin/planches' },
            { name: 'Ajouter des planches', url: '/admin/planches/create' },
          //  { name: 'Contrats',             url: '/admin/contrats' },
        ],
    },
    {
        name: 'Factures',
        icon: 'fa fa-file-text',
        url: '/admin/planche-bons-livraison',
        roles: ['admin', 'comptable'],
        subMenu: [],
    },
    {
        name: 'Clients',
        icon: 'fa fa-users',
        roles: ['admin'],
        subMenu: [
            { name: 'Liste des clients',          url: '/admin/clients' },
            { name: 'Comptes clients',             url: '/admin/clients/comptes' },
            { name: 'Historique comptabilité',     url: '/admin/clients/historique-comptabilite' },
        ],
    },
    {
        name: 'Finances',
        icon: 'fa fa-money',
        roles: ['admin'],
        subMenu: [
            { name: 'Gestion des caisses', url: '/admin/finances/caisses' },
            { name: 'Historique caisse',   url: '/admin/finances/caisse' },
            { name: 'Rapports',            url: '/admin/finances/rapports' },
        ],
    },
    {
        name: 'Configuration',
        icon: 'fa fa-cogs',
        roles: ['admin'],
        subMenu: [
            { name: 'Paramètres',          url: '/admin/configuration' },
            { name: 'Tarifs prix de revient', url: '/admin/configuration/planche-tarifs' },
        ],
    },
    {
        name: 'Utilisateurs',
        icon: 'fa fa-user-circle',
        url: '/admin/users',
        roles: ['admin'],
        subMenu: [],
    },
];

const menuItems = computed(() => {
    const role = authUser.value?.role;
    return allMenuItems.filter(item => item.roles.includes(role));
});

const toggleSubMenu = (menuName) => {
    activeSubMenu.value = activeSubMenu.value === menuName ? null : menuName;
};

const closeSubMenuOnOutsideClick = (event) => {
    const menuElement = document.getElementById('left-sidebar');

    if (menuElement && !menuElement.contains(event.target)) {
        activeSubMenu.value = null;
    }
};

const finishPageLoading = () => {
    requestAnimationFrame(() => {
        window.setTimeout(() => {
            isPageLoading.value = false;
        }, 180);
    });
};

onMounted(() => {
    document.addEventListener('click', closeSubMenuOnOutsideClick);

    removeInertiaStartListener = router.on('start', () => {
        isPageLoading.value = true;
    });

    removeInertiaFinishListener = router.on('finish', () => {
        finishPageLoading();
    });

    removeInertiaErrorListener = router.on('error', () => {
        isPageLoading.value = false;
    });

    if (document.readyState === 'complete') {
        nextTick(() => {
            finishPageLoading();
        });
        return;
    }

    const handleWindowLoad = () => {
        finishPageLoading();
    };

    window.addEventListener('load', handleWindowLoad, { once: true });
    removeWindowLoadListener = () => {
        window.removeEventListener('load', handleWindowLoad);
    };
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeSubMenuOnOutsideClick);
    removeInertiaStartListener?.();
    removeInertiaFinishListener?.();
    removeInertiaErrorListener?.();
    removeWindowLoadListener?.();
});

function toggleSidebar() {
    sidebarVisible.value = !sidebarVisible.value;
    document.body.classList.toggle('layout-fullwidth', sidebarVisible.value);
}

function logout() {
    router.post(route('logout'));
}
</script>

<style>
#main-content {
    padding-top: 20px !important;
}

.page-loader-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, rgba(246, 248, 250, 0.98), rgba(232, 238, 242, 0.96));
    backdrop-filter: blur(3px);
}

.page-loader-card {
    min-width: 240px;
    padding: 24px 28px;
    text-align: center;
    border-radius: 18px;
    background: #ffffff;
    box-shadow: 0 18px 60px rgba(18, 38, 63, 0.16);
}

.page-loader-spinner {
    width: 48px;
    height: 48px;
    margin: 0 auto 14px;
    border: 4px solid #d9e2ec;
    border-top-color: #00a0b0;
    border-radius: 50%;
    animation: page-loader-spin 0.8s linear infinite;
}

.page-loader-text {
    margin: 0;
    color: #3b4a5a;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.02em;
}

.page-loader-enter-active,
.page-loader-leave-active {
    transition: opacity 0.2s ease;
}

.page-loader-enter-from,
.page-loader-leave-to {
    opacity: 0;
}

.metismenu .collapse.in,
.metismenu .collapsing {
    opacity: 1 !important;
    visibility: visible !important;
}

@keyframes page-loader-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
