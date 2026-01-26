import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/',
        name: 'home',
        component: () => import('./views/Home.vue'),
    },
    {
        path: '/login',
        name: 'login',
        component: () => import('./views/Login.vue'),
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('./views/Register.vue'),
    },
    {
        path: '/scenarios',
        name: 'scenarios',
        component: () => import('./views/Scenarios.vue'),
    },
    {
        path: '/scenarios/create',
        name: 'scenarios.create',
        component: () => import('./views/ScenarioEditor.vue'),
    },
    {
        path: '/scenarios/:id/edit',
        name: 'scenarios.edit',
        component: () => import('./views/ScenarioEditor.vue'),
    },
    {
        path: '/groups',
        name: 'groups',
        component: () => import('./views/Groups.vue'),
    },
    {
        path: '/drill',
        name: 'drill',
        component: () => import('./views/DrillSetup.vue'),
    },
    {
        path: '/drill/active',
        name: 'drill.active',
        component: () => import('./views/DrillActive.vue'),
    },
    {
        path: '/drill/summary',
        name: 'drill.summary',
        component: () => import('./views/DrillSummary.vue'),
    },
    {
        path: '/drill/range-construction',
        name: 'drill.rangeConstruction',
        component: () => import('./views/RangeConstructionDrill.vue'),
    },
    {
        path: '/stats',
        name: 'stats',
        component: () => import('./views/Stats.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
