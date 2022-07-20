
const Index = () => import('./components/l-limitless-bs4/Index');
const Form = () => import('./components/l-limitless-bs4/Form');
const Show = () => import('./components/l-limitless-bs4/Show');
const SideBarLeft = () => import('./components/l-limitless-bs4/SideBarLeft');
const SideBarRight = () => import('./components/l-limitless-bs4/SideBarRight');

const routes = [

    {
        path: '/goods-returned',
        components: {
            default: Index,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Goods Returned Note',
            metaTags: [
                {
                    name: 'description',
                    content: 'Goods Returned Note'
                },
                {
                    property: 'og:description',
                    content: 'Goods Returned Note'
                }
            ]
        }
    },
    {
        path: '/goods-returned/create',
        components: {
            default: Form,
            //'sidebar-left': ComponentSidebarLeft,
            //'sidebar-right': ComponentSidebarRight
        },
        meta: {
            title: 'Goods Returned Note :: Create',
            metaTags: [
                {
                    name: 'description',
                    content: 'Create Goods Returned Note'
                },
                {
                    property: 'og:description',
                    content: 'Create Goods Returned Note'
                }
            ]
        }
    },
    {
        path: '/goods-returned/:id',
        components: {
            default: Show,
            'sidebar-left': SideBarLeft,
            'sidebar-right': SideBarRight
        },
        meta: {
            title: 'Goods Returned Note',
            metaTags: [
                {
                    name: 'description',
                    content: 'Goods Returned Note'
                },
                {
                    property: 'og:description',
                    content: 'Goods Returned Note'
                }
            ]
        }
    },
    {
        path: '/goods-returned/:id/copy',
        components: {
            default: Form,
        },
        meta: {
            title: 'Goods Returned Note :: Copy',
            metaTags: [
                {
                    name: 'description',
                    content: 'Copy Goods Returned Note'
                },
                {
                    property: 'og:description',
                    content: 'Copy Goods Returned Note'
                }
            ]
        }
    },
    {
        path: '/goods-returned/:id/edit',
        components: {
            default: Form,
        },
        meta: {
            title: 'Goods Returned Note :: Edit',
            metaTags: [
                {
                    name: 'description',
                    content: 'Edit Goods Returned Note'
                },
                {
                    property: 'og:description',
                    content: 'Edit Goods Returned Note'
                }
            ]
        }
    }

]

export default routes
