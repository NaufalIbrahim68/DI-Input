<style>
    /* Sidebar collapsed state - icon on top, text below */
    .sidebar.toggled {
        width: 110px !important;
        min-width: 110px !important;
        overflow: visible !important;
    }

    .sidebar.toggled .nav-item .nav-link {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        padding: 12px 8px !important;
    }

    .sidebar.toggled .nav-item .nav-link i {
        margin-right: 0 !important;
        margin-bottom: 4px !important;
        font-size: 1.1rem !important;
    }

    .sidebar.toggled .nav-item .nav-link span {
        display: block !important;
        font-size: 0.65rem !important;
        line-height: 1.2 !important;
        opacity: 1 !important;
        width: 100% !important;
        height: auto !important;
        visibility: visible !important;
    }

    .sidebar.toggled .sidebar-heading {
        text-align: center !important;
        font-size: 0.6rem !important;
        visibility: visible !important;
        opacity: 1 !important;
        display: block !important;
        width: 100% !important;
        height: auto !important;
    }

    .sidebar.toggled .sidebar-brand {
        padding: 10px 5px !important;
    }

    .sidebar.toggled .sidebar-brand .sidebar-brand-icon img {
        max-height: 30px !important;
    }

    /* Logout button in collapsed state */
    .sidebar.toggled .nav-item form button.nav-link {
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        padding: 12px 8px !important;
        width: 100% !important;
    }

    .sidebar.toggled .nav-item form button.nav-link i {
        margin-right: 0 !important;
        margin-bottom: 4px !important;
    }

    .sidebar.toggled .nav-item form button.nav-link span {
        display: block !important;
        font-size: 0.65rem !important;
        line-height: 1.2 !important;
    }

    /* Fix sidebar toggle button position */
    .sidebar.toggled #sidebarToggle {
        margin: 0 auto;
    }
</style>

<ul class="navbar-nav bg-white sidebar accordion shadow" id="accordionSidebar"
    style="min-height: 100vh; width: 250px; color: #000;">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center py-3" href="{{ url('/dashboard') }}">
        <div class="sidebar-brand-icon">
            <img src="{{ asset('assets/images/AVI.png') }}" alt="AVI LOGO" class="img-fluid"
                style="max-height: 40px; width: auto;">
        </div>
    </a>

    <hr class="sidebar-divider my-0">

    <!-- Dashboard -->
    <li class="nav-item {{ request()->is('dashboard') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center text-black" href="{{ url('/dashboard') }}">
            <i class="fas fa-th me-2 text-black"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Data Section -->
    <div class="sidebar-heading px-3 text-black">DATA</div>

    <!-- Data DI -->
    <li class="nav-item {{ request()->is('DI_Input*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center text-black" href="{{ route('DI_Input.index') }}">
            <i class="fas fa-database me-2 text-black"></i>
            <span>Data DI</span>
        </a>
    </li>

    <li class="nav-item {{ request()->is('ds-input') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center text-black" href="{{ route('ds_input.generatePage') }}"> <i
                class="fas fa-rocket me-2 text-black"></i> <span>Generate DS</span> </a>

        {{-- Data DS --}}
    <li class="nav-item {{ request()->is('ds-input') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center text-black" href="{{ route('ds_input.index') }}">
            <i class="fas fa-table me-2 text-black"></i>
            <span>Data DS</span>
        </a>
    </li>

    <hr class="sidebar-divider">

    <!-- Logout -->
    <li class="nav-item">
        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="margin: 0;">
            @csrf
            <button type="submit" class="btn btn-link nav-link text-black d-flex align-items-center w-100 text-start"
                style="border: none; background: none; padding: 0.75rem 1rem;"
                onclick="event.preventDefault(); if(confirm('Apakah Anda yakin ingin logout?')) { this.closest('form').submit(); }">
                <i class="fas fa-sign-out-alt me-2 text-black"></i>
                <span>Logout</span>
            </button>
        </form>
    </li>

    <hr class="sidebar-divider">

    <!-- Sidebar Toggle -->
    <div class="text-center d-none d-md-inline my-3">
        <button class="rounded-circle border-0 shadow" id="sidebarToggle"></button>
    </div>

</ul>
