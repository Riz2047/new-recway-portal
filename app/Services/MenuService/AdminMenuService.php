<?php

declare(strict_types=1);

namespace App\Services\MenuService;

use App\Enums\Hooks\AdminFilterHook;
use App\Models\ServiceCategory;
use App\Services\Content\ContentService;
use App\Support\Facades\Hook;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class AdminMenuService
{
    /**
     * @var AdminMenuItem[][]
     */
    protected array $groups = [];

    /**
     * Add a menu item to the admin sidebar.
     *
     * @param  AdminMenuItem|array  $item  The menu item or configuration array
     * @param  string|null  $group  The group to add the item to
     *
     * @throws \InvalidArgumentException
     */
    public function addMenuItem(AdminMenuItem|array $item, ?string $group = null): void
    {
        $group = $group ?: __('Main');
        $menuItem = $this->createAdminMenuItem($item);
        if (! isset($this->groups[$group])) {
            $this->groups[$group] = [];
        }

        if ($menuItem->userHasPermission()) {
            $this->groups[$group][] = $menuItem;
        }
    }

    protected function createAdminMenuItem(AdminMenuItem|array $data): AdminMenuItem
    {
        if ($data instanceof AdminMenuItem) {
            return $data;
        }

        $menuItem = new AdminMenuItem();

        if (isset($data['children']) && is_array($data['children'])) {
            $data['children'] = array_map(
                function ($child) {
                    // Check if user is authenticated
                    $user = auth()->user();
                    if (! $user) {
                        return null;
                    }

                    // Handle permissions.
                    if (isset($child['permission'])) {
                        $child['permissions'] = $child['permission'];
                        unset($child['permission']);
                    }

                    $permissions = $child['permissions'] ?? [];
                    if (empty($permissions) || $user->hasAnyPermission((array) $permissions)) {
                        return $this->createAdminMenuItem($child);
                    }

                    return null;
                },
                $data['children']
            );

            // Filter out null values (items without permission).
            $data['children'] = array_filter($data['children']);
        }

        // Convert 'permission' to 'permissions' for consistency
        if (isset($data['permission'])) {
            $data['permissions'] = $data['permission'];
            unset($data['permission']);
        }

        // Handle route with params
        if (isset($data['route']) && isset($data['params'])) {
            $routeName = $data['route'];
            $params = $data['params'];

            if (is_array($params)) {
                $data['route'] = route($routeName, $params);
            } else {
                $data['route'] = route($routeName, [$params]);
            }
        }

        return $menuItem->setAttributes($data);
    }

    public function getMenu()
    {
        $prefix = request()->segment(1);
        $this->addMenuItem([
            'label' => __('Dashboard'),
            'icon' => 'lucide:layout-dashboard',
            'route' => route("$prefix.dashboard"),
            'active' => Route::is("$prefix.dashboard"),
            'id' => 'dashboard',
            'priority' => 1,
            'permissions' => 'dashboard.view',
        ]);

        $this->addMenuItem([
            'label' => __('Admins'),
            'icon' => 'lucide:user',
            'route' => route("$prefix.admins.index"),
            'active' => Route::is("$prefix.admins.*"),
            'id' => 'admins',
            'priority' => 2,
            'permissions' => 'user.view',
        ]);

        $this->addMenuItem([
            'label' => __('Staff'),
            'icon' => 'lucide:users',
            'id' => 'staff-submenu',
            'active' => Route::is("$prefix.staff.*"),
            'priority' => 3,
            'permissions' => ['staff.view', 'staff.create', 'staff.edit', 'staff.delete'],
            'children' => [
                [
                    'label' => __('All Staff'),
                    'route' => route("$prefix.staff.index"),
                    'active' => Route::is("$prefix.staff.*"),
                    'priority' => 10,
                    'permissions' => ['staff.view', 'staff.create', 'staff.edit', 'staff.delete'],
                ],
                [
                    'label' => __('Staff Category'),
                    'route' => route("$prefix.staff-category.index"),
                    'active' => Route::is("$prefix.staff-category.*"),
                    'priority' => 20,
                    'permissions' => ['staff-category.view', 'staff-category.create', 'staff-category.edit', 'staff-category.delete'],
                ],
            ],
        ]);

        $this->addMenuItem([
            'label' => __('Services'),
            'icon' => 'lucide:briefcase',
            'route' => route("$prefix.service-category.index"),
            'active' => Route::is("$prefix.service-category.*") || Route::is("$prefix.service.*"),
            'id' => 'services',
            'priority' => 4,
              'permissions' => ['service.view', 'service-category.create', 'service-category.edit', 'service-category.delete'],
        ]);

        // Statuses dropdown menu with service categories
        $serviceCategories = ServiceCategory::orderBy('name')->get();
        $statusChildren = [];

        foreach ($serviceCategories as $index => $serviceCategory) {
            $statusChildren[] = [
                'label' => $serviceCategory->name,
                'route' => route("$prefix.status.index", $serviceCategory->id),
                'active' => Route::is("$prefix.status.*") && (int) request()->route('serviceCategory') === $serviceCategory->id,
                'priority' => ($index + 1) * 10,
                'permissions' => ['status.view', 'status.create', 'status.edit', 'status.delete'],
            ];
        }

        if (! empty($statusChildren)) {
            $this->addMenuItem([
                'label' => __('Statuses'),
                'icon' => 'lucide:list-checks',
                'id' => 'statuses-submenu',
                'active' => Route::is("$prefix.status.*"),
                'priority' => 5,
                'permissions' => 'status.view',
                'children' => $statusChildren,
            ]);
        }

        $this->addMenuItem([
            'label' => __('Places'),
            'icon' => 'lucide:map-pin',
            'route' => route("$prefix.place.index"),
            'active' => Route::is("$prefix.place.*"),
            'id' => 'places',
            'priority' => 6,
            'permissions' => ['place.view', 'place.create', 'place.edit', 'place.delete'],
        ]);

        $this->addMenuItem([
            'label' => __('Customers'),
            'icon' => 'lucide:user-circle',
            'route' => route("$prefix.customers.index"),
            'active' => Route::is("$prefix.customers.*"),
            'id' => 'customers',
            'priority' => 7,
              'permissions' => ['customer.view'],
        ]);

        $this->addMenuItem([
            'label' => __('Candidates'),
            'icon' => 'lucide:file-user',
            'route' => route("$prefix.candidates.index"),
            'active' => Route::is("$prefix.candidates.*"),
            'id' => 'candidates',
            'priority' => 8,
            'permissions' => ['customer.view'],
        ]);

        $this->addMenuItem([
            'label' => __('Email Templates'),
            'icon' => 'lucide:mail',
            'route' => route("$prefix.email-templates.index"),
            'active' => Route::is("$prefix.email-templates.*"),
            'id' => 'email-templates',
            'priority' => 9,
            'permissions' => ['email_template.view'],
        ]);

        $this->addMenuItem([
            'label' => __('Reports'),
            'icon' => 'lucide:file-text',
            'route' => route("$prefix.reports.index"),
            'active' => Route::is("$prefix.reports.*"),
            'id' => 'reports',
            'priority' => 10,
            'permissions' => ['customer.view'],
        ]);

        $this->addMenuItem([
            'label' => __('Statistics'),
            'icon' => 'lucide:bar-chart-2',
            'route' => route("$prefix.analytics.index"),
            'active' => Route::is("$prefix.analytics.*"),
            'id' => 'statistics',
            'priority' => 11,
            'permissions' => ['customer.view'],
        ]);

        $this->addMenuItem([
            'label' => __('Messages'),
            'icon' => 'lucide:message-square',
            'route' => route("$prefix.message-templates.index"),
            'active' => Route::is("$prefix.message-templates.*"),
            'id' => 'messages',
            'priority' => 12,
            'permissions' => ['customer.view'],
        ]);

        // $this->registerPostTypesInMenu(null);

        $this->addMenuItem([
            'label' => __('Media Library'),
            'icon' => 'lucide:image',
            'route' => route("$prefix.media.index"),
            'active' => Route::is("$prefix.media.*"),
            'id' => 'media',
            'priority' => 35,
            'permissions' => ['media.view', 'media.create', 'media.edit', 'media.delete'],
        ]);
        // $this->addMenuItem([
        //     'label' => __('Modules'),
        //     'icon' => 'lucide:boxes',
        //     'route' => route("$prefix.modules.index"),
        //     'active' => Route::is("$prefix.modules.index"),
        //     'id' => 'modules',
        //     'priority' => 25,
        //     'permissions' => ['module.view'],
        // ], __('More'));

        $this->addMenuItem([
            'label' => __('Monitoring'),
            'icon' => 'lucide:monitor',
            'id' => 'monitoring-submenu',
            'active' => Route::is("$prefix.actionlog.*"),
            'priority' => 50,
            'permissions' => ['pulse.view', 'actionlog.view'],
            'children' => [
                [
                    'label' => __('Action Logs'),
                    'route' => route("$prefix.actionlog.index"),
                    'active' => Route::is("$prefix.actionlog.index"),
                    'priority' => 10,
                    'permissions' => ['actionlog.view'],
                ],
                [
                    'label' => __('Laravel Pulse'),
                    'route' => route('pulse'),
                    'active' => false,
                    'target' => '_blank',
                    'priority' => 20,
                    'permissions' => ['pulse.view'],
                ],
            ],
        ], __('More'));

        $this->addMenuItem(
            [
                'label' => __('Access Control'),
                'icon' => 'lucide:key',
                'id' => 'access-control-submenu',
                'active' => Route::is("$prefix.roles.*") || Route::is("$prefix.permissions.*") || Route::is("$prefix.users.*"),
                'priority' => 30,
                'permissions' => ['role.create', 'role.view', 'role.edit', 'role.delete', 'user.create', 'user.view', 'user.edit', 'user.delete'],
                'children' => [
                    [
                        'label' => __('Users'),
                        'route' => route("$prefix.users.index"),
                        'active' => Route::is("$prefix.users.index") || Route::is("$prefix.users.create") || Route::is("$prefix.users.edit"),
                        'priority' => 10,
                        'permissions' => ['user.view'],
                    ],
                    [
                        'label' => __('Roles'),
                        'route' => route("$prefix.roles.index"),
                        'active' => Route::is("$prefix.roles.index") || Route::is("$prefix.roles.create") || Route::is("$prefix.roles.edit"),
                        'priority' => 20,
                        'permissions' => ['role.view'],
                    ],
                    [
                        'label' => __('Permissions'),
                        'route' => route("$prefix.permissions.index"),
                        'active' => Route::is("$prefix.permissions.index") || Route::is("$prefix.permissions.show"),
                        'priority' => 30,
                        'permissions' => 'role.view',
                    ],
                ],
            ],
            __('More')
        );

        $this->addMenuItem([
            'label' => __('Settings'),
            'icon' => 'lucide:settings',
            'id' => 'settings-submenu',
            'active' => Route::is("$prefix.settings.*") || Route::is("$prefix.translations.*"),
            'priority' => 40,
            'permissions' => ['settings.edit', 'translations.view'],
            'children' => [
                [
                    'label' => __('Settings'),
                    'route' => route("$prefix.settings.index"),
                    'active' => Route::is("$prefix.settings.index"),
                    'priority' => 20,
                    'permissions' => ['settings.edit'],
                ],
                [
                    'label' => __('Translations'),
                    'route' => route("$prefix.translations.index"),
                    'active' => Route::is("$prefix.translations.*"),
                    'priority' => 10,
                    'permissions' => ['translations.view', 'translations.edit'],
                ],
            ],
        ], __('More'));

        $this->addMenuItem([
            'label' => __('Logout'),
            'icon' => 'lucide:log-out',
            'route' => route("$prefix.dashboard"),
            'active' => false,
            'id' => 'logout',
            'priority' => 10000,
            'html' => '
                <li>
                    <form method="POST" action="' . route("$prefix.logout.submit") . '">
                        ' . csrf_field() . '
                        <button type="submit" class="menu-item group w-full text-left menu-item-inactive text-gray-700 dark:text-white hover:text-gray-700">
                            <iconify-icon icon="lucide:log-out" class="menu-item-icon " width="16" height="16"></iconify-icon>
                            <span class="menu-item-text">' . __('Logout') . '</span>
                        </button>
                    </form>
                </li>
            ',
        ], __('More'));

        $this->groups = Hook::applyFilters(AdminFilterHook::ADMIN_MENU_GROUPS_BEFORE_SORTING, $this->groups);

        $this->sortMenuItemsByPriority();

        return $this->applyFiltersToMenuItems();
    }

    /**
     * Register post types in the menu
     * Move to main group if $group is null
     */
    // protected function registerPostTypesInMenu(?string $group = 'Content'): void
    // {
    //     $contentService = app(ContentService::class);
    //     $postTypes = $contentService->getPostTypes();

    //     if ($postTypes->isEmpty()) {
    //         return;
    //     }

    //     foreach ($postTypes as $typeName => $type) {
    //         // Skip if not showing in menu.
    //         if (isset($type->show_in_menu) && ! $type->show_in_menu) {
    //             continue;
    //         }

    //         // Create children menu items.
    //         $children = [
    //             [
    //                 'title' => __("All {$type->label}"),
    //                 'route' => 'admin.posts.index',
    //                 'params' => $typeName,
    //                 'active' => request()->is('admin/posts/' . $typeName) ||
    //                     (request()->is('admin/posts/' . $typeName . '/*') && ! request()->is('admin/posts/' . $typeName . '/create')),
    //                 'priority' => 10,
    //                 'permissions' => 'post.view',
    //             ],
    //             [
    //                 'title' => __('Add New'),
    //                 'route' => 'admin.posts.create',
    //                 'params' => $typeName,
    //                 'active' => request()->is('admin/posts/' . $typeName . '/create'),
    //                 'priority' => 20,
    //                 'permissions' => 'post.create',
    //             ],
    //         ];

    //         // Add taxonomies as children of this post type if this post type has them.
    //         if (! empty($type->taxonomies)) {
    //             $taxonomies = $contentService->getTaxonomies()
    //                 ->whereIn('name', $type->taxonomies);

    //             foreach ($taxonomies as $taxonomy) {
    //                 $children[] = [
    //                     'title' => __($taxonomy->label),
    //                     'route' => 'admin.terms.index',
    //                     'params' => $taxonomy->name,
    //                     'active' => request()->is('admin/terms/' . $taxonomy->name . '*'),
    //                     'priority' => 30 + $taxonomy->id, // Prioritize after standard items
    //                     'permissions' => 'term.view',
    //                 ];
    //             }
    //         }

    //         // Set up menu item with all children.
    //         $menuItem = [
    //             'title' => __($type->label),
    //             'icon' => get_post_type_icon($typeName),
    //             'id' => 'post-type-' . $typeName,
    //             'active' => request()->is('admin/posts/' . $typeName . '*') ||
    //                 (! empty($type->taxonomies) && $this->isCurrentTermBelongsToPostType($type->taxonomies)),
    //             'priority' => 10,
    //             'permissions' => 'post.view',
    //             'children' => $children,
    //         ];

    //         $this->addMenuItem($menuItem, $group ?: __('Main'));
    //     }
    // }

    /**
     * Check if the current term route belongs to the given taxonomies
     */
    // protected function isCurrentTermBelongsToPostType(array $taxonomies): bool
    // {
    //     if (! request()->is('admin/terms/*')) {
    //         return false;
    //     }

    //     // Get the current taxonomy from the route
    //     $currentTaxonomy = request()->segment(3); // admin/terms/{taxonomy}

    //     return in_array($currentTaxonomy, $taxonomies);
    // }

    protected function sortMenuItemsByPriority(): void
    {
        foreach ($this->groups as &$groupItems) {
            usort($groupItems, function ($a, $b) {
                return (int) $a->priority <=> (int) $b->priority;
            });
        }
    }

    protected function applyFiltersToMenuItems(): array
    {
        $result = [];
        foreach ($this->groups as $group => $items) {
            // Filter items by permission.
            $filteredItems = array_filter($items, function (AdminMenuItem $item) {
                return $item->userHasPermission();
            });

            // Apply filters that might add/modify menu items.
            $filteredItems = Hook::applyFilters(AdminFilterHook::SIDEBAR_MENU->value . strtolower((string) $group), $filteredItems);

            // Only add the group if it has items after filtering.
            if (! empty($filteredItems)) {
                $result[$group] = $filteredItems;
            }
        }

        return $result;
    }

    public function shouldExpandSubmenu(AdminMenuItem $menuItem): bool
    {
        // If the parent menu item is active, expand the submenu.
        if ($menuItem->active) {
            return true;
        }

        // Check if any child menu item is active.
        foreach ($menuItem->children as $child) {
            if ($child->active) {
                return true;
            }
        }

        return false;
    }

    public function render(array $groupItems): string
    {
        $html = '';
        foreach ($groupItems as $menuItem) {
            $filterKey = $menuItem->id ?? Str::slug($menuItem->label) ?: '';
            $html .= Hook::applyFilters(AdminFilterHook::SIDEBAR_MENU_BEFORE->value . $filterKey, '');

            $html .= view('backend.layouts.partials.sidebar.menu-item', [
                'item' => $menuItem,
            ])->render();

            $html .= Hook::applyFilters(AdminFilterHook::SIDEBAR_MENU_AFTER->value . $filterKey, '');
        }

        return $html;
    }
}
