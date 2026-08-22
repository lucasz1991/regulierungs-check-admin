<nav class="metismenu" id="sidebar-menu" aria-label="Administrationsbereiche">
    <ul id="side-menu">
        @can('admin.dashboard.view')
            <x-menu.sidebar-nav label="Übersicht">
                <x-menu.sidebar-nav-link :href="route('admin.index')" icon="home" :active="request()->routeIs('admin.index')">Dashboard</x-menu.sidebar-nav-link>
            </x-menu.sidebar-nav>
        @endcan

        @canany(['promotion.wins.record', 'promotion.campaigns.manage'])
            <x-menu.sidebar-nav label="Promotion">
                @can('promotion.wins.record')<x-menu.sidebar-nav-link :href="route('promotion.console')" icon="award" :active="request()->routeIs('promotion.*')">Promotion-Konsole</x-menu.sidebar-nav-link>@endcan
                @can('promotion.campaigns.manage')<x-menu.sidebar-nav-link :href="route('admin.promotion')" icon="gift" :active="request()->routeIs('admin.promotion')">Kampagnen verwalten</x-menu.sidebar-nav-link>@endcan
            </x-menu.sidebar-nav>
        @endcanany

        @canany(['tasks.manage', 'messages.manage', 'mails.manage', 'contacts.manage', 'users.manage', 'reviews.manage'])
            <x-menu.sidebar-nav label="Tagesgeschäft">
                @can('tasks.manage')<x-menu.sidebar-nav-link :href="route('admin.tasks')" icon="list" :active="request()->routeIs('admin.tasks')">Aufgaben</x-menu.sidebar-nav-link>@endcan
                @can('messages.manage')<x-menu.sidebar-nav-link :href="route('admin.messages')" icon="message-square" :active="request()->routeIs('admin.messages')">Nachrichten</x-menu.sidebar-nav-link>@endcan
                @can('reviews.manage')<x-menu.sidebar-nav-link :href="route('admin.reviews.claim-ratings')" icon="calendar" :active="request()->routeIs('admin.reviews.claim-ratings', 'admin.reviews.show')">Bewertungen</x-menu.sidebar-nav-link>@endcan
                @can('users.manage')<x-menu.sidebar-nav-link :href="route('admin.users')" icon="users" :active="request()->routeIs('admin.users', 'admin.user-profile')">Benutzer</x-menu.sidebar-nav-link>@endcan
                @can('contacts.manage')<x-menu.sidebar-nav-link :href="route('admin.contacts')" icon="book-open" :active="request()->routeIs('admin.contacts')">Kontakte</x-menu.sidebar-nav-link>@endcan
                @can('mails.manage')<x-menu.sidebar-nav-link :href="route('admin.mails')" icon="mail" :active="request()->routeIs('admin.mails')">E-Mails</x-menu.sidebar-nav-link>@endcan
            </x-menu.sidebar-nav>
        @endcanany

        @canany(['content.web.manage', 'content.news.manage', 'content.pagebuilder.manage', 'ratings.structure.manage'])
            <x-menu.sidebar-nav label="Inhalte">
                @can('content.news.manage')<x-menu.sidebar-nav-link :href="route('admin.webcontent.news')" icon="file-text" :active="request()->routeIs('admin.webcontent.news')">News</x-menu.sidebar-nav-link>@endcan
                @can('content.web.manage')<x-menu.sidebar-nav-link :href="route('admin.webcontentmanager')" icon="grid" :active="request()->routeIs('admin.webcontentmanager')">Web-Inhalte</x-menu.sidebar-nav-link>@endcan
                @can('content.pagebuilder.manage')<x-menu.sidebar-nav-link :href="route('admin.cms.edit-project')" icon="layout" :active="request()->routeIs('admin.cms.edit-project')">Pagebuilder</x-menu.sidebar-nav-link>@endcan
                @can('ratings.structure.manage')<x-menu.sidebar-nav-link :href="route('admin.ratingstructure.index')" icon="help-circle" :active="request()->routeIs('admin.ratingstructure.index')">Bewertungsstruktur</x-menu.sidebar-nav-link>@endcan
            </x-menu.sidebar-nav>
        @endcanany

        @canany(['settings.manage', 'staff.manage', 'roles.manage', 'audit.view', 'exports.manage'])
            <x-menu.sidebar-nav label="Administration">
                @can('settings.manage')<x-menu.sidebar-nav-link :href="route('admin.config')" icon="settings" :active="request()->routeIs('admin.config')">Einstellungen</x-menu.sidebar-nav-link>@endcan
                @can('staff.manage')<x-menu.sidebar-nav-link :href="route('admin.employees')" icon="users" :active="request()->routeIs('admin.employees')">Mitarbeiter</x-menu.sidebar-nav-link>@endcan
                @can('roles.manage')<x-menu.sidebar-nav-link :href="route('admin.team-permissions')" icon="shield" :active="request()->routeIs('admin.team-permissions')">Teams &amp; Rechte</x-menu.sidebar-nav-link>@endcan
                @can('audit.view')<x-menu.sidebar-nav-link :href="route('admin.safety')" icon="activity" :active="request()->routeIs('admin.safety')">Aktivitäten</x-menu.sidebar-nav-link>@endcan
                @can('exports.manage')<x-menu.sidebar-nav-link :href="route('admin.exports')" icon="sliders" :active="request()->routeIs('admin.exports')">Exporte</x-menu.sidebar-nav-link>@endcan
            </x-menu.sidebar-nav>
        @endcanany
    </ul>
</nav>
