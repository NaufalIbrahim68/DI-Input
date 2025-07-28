<ul class="navbar-nav bg-gradient-dark sidebar sidebar-dark accordion shadow" id="accordionSidebar">

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
        <a class="nav-link d-flex align-items-center" href="{{ url('/dashboard') }}">
            <i class="fas fa-fw fa-tachometer-alt me-2"></i>
            <span>Dashboard</span>
        </a>
    </li>

    <!-- Data DI -->
    <li class="nav-item {{ request()->is('DI_Input*') ? 'active' : '' }}">
        <a class="nav-link d-flex align-items-center" href="{{ route('DI_Input.index') }}">
            <i class="fas fa-fw fa-database me-2"></i>
            <span>Data DI</span>
        </a>
    </li>

   <!-- Logout -->
<li class="nav-item">
    <a class="nav-link d-flex align-items-center" href="#" onclick="confirmLogout(event)">
        <i class="fas fa-sign-out-alt me-2"></i>
        <span>Logout</span>
    </a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</li>

@push('scripts')
<script>
    function confirmLogout(event) {
        event.preventDefault();
        if (confirm("Apakah Anda yakin ingin logout?")) {
            document.getElementById('logout-form').submit();
        }
    }
</script>
@endpush

    <hr class="sidebar-divider">

    <div class="text-center d-none d-md-inline my-3">
        <button class="rounded-circle border-0 shadow" id="sidebarToggle"></button>
    </div>

</ul>
