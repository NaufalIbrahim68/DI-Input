<ul class="navbar-nav bg-white sidebar accordion shadow" id="accordionSidebar" style="min-height: 100vh; width: 250px; color: #000;">
    
    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center py-3" href="{{ url('/dashboard') }}">
        <div class="sidebar-brand-icon">
            <img src="{{ asset('assets/images/AVI.png') }}" alt="AVI LOGO" class="img-fluid" style="max-height: 40px; width: auto;">
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


    <!-- Data DS -->
    <li class="nav-item {{ request()->is('ds-input*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center text-black" href="{{ route('ds_input.index') }}">
            <i class="fas fa-table me-2 text-black"></i>
            <span>Generate DS</span>
        </a>
    </li>


     <!-- DN -->
<li class="nav-item {{ request()->is('dn*') ? 'active' : '' }}">
    <a class="nav-link d-flex align-items-center text-black" href="{{ route('dn.index') }}">
        <i class="fas fa-truck me-2 text-black"></i>
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