<div class="metismenu pb-10 pt-2.5" id="sidebar-menu">
    <ul id="side-menu">
        <x-menu.sidebar-nav>
            <x-menu.sidebar-nav-link :href="route('admin.index')" icon="home" :active="request()->routeIs('admin.index')">
                Dashboard
            </x-menu.sidebar-nav-link>
        </x-menu.sidebar-nav>

        <x-menu.sidebar-nav label="System-Verwaltung">
            <x-menu.sidebar-nav-link :href="route('admin.config')" icon="settings" :active="request()->routeIs('admin.config')">
                Einstellungen
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.webcontentmanager')" icon="grid" :active="request()->routeIs('admin.webcontentmanager', 'admin.cms.edit-project')">
                Web Inhalte
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.webcontent.news')" icon="file-text" :active="request()->routeIs('admin.webcontent.news')">
                News
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.ratingstructure.index')" icon="help-circle" :active="request()->routeIs('admin.ratingstructure.index')">
                Bewertungsstruktur
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.employees')" icon="users" :active="request()->routeIs('admin.employees')">
                Mitarbeiter
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.safety')" icon="activity" :active="request()->routeIs('admin.safety')">
                Aktivit&auml;ten
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.exports')" icon="sliders" :active="request()->routeIs('admin.exports')">
                Exporte
            </x-menu.sidebar-nav-link>
        </x-menu.sidebar-nav>

        <x-menu.sidebar-nav label="Management">
            <x-menu.sidebar-nav-link :href="route('admin.tasks')" icon="list" :active="request()->routeIs('admin.tasks')">
                To-Do's
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.mails')" icon="mail" :active="request()->routeIs('admin.mails')">
                Mails
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.users')" icon="users" :active="request()->routeIs('admin.users', 'admin.user-profile')">
                Benutzer
            </x-menu.sidebar-nav-link>

            <x-menu.sidebar-nav-link :href="route('admin.reviews.claim-ratings')" icon="calendar" :active="request()->routeIs('admin.reviews.claim-ratings', 'admin.reviews.show')">
                Bewertungen
            </x-menu.sidebar-nav-link>

            @if(Auth::user()?->role == 'admin')
                <x-menu.sidebar-nav-link :href="route('admin.users')" icon="box" :active="false">
                    Auswertungen
                </x-menu.sidebar-nav-link>

                <x-menu.sidebar-nav-link :href="route('admin.users')" icon="shopping-cart" :active="false">
                    Anfragen
                </x-menu.sidebar-nav-link>
            @endif
        </x-menu.sidebar-nav>
    </ul>
</div>
