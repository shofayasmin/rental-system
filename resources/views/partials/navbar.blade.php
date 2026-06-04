<nav class="navbar navbar-expand-lg navbar-light no-print sticky-top"
     style="background-color: #FFCE1B; z-index: 1040; border-bottom: none;">
    <div class="container">

        @auth
            @if(auth()->user()->role === 'admin')
                <span class="navbar-brand fw-bold mb-0">House Rental</span>
            @elseif(auth()->user()->role === 'agent')
                <span class="navbar-brand fw-bold mb-0">House Rental</span>
            @else
                <a class="navbar-brand fw-bold" href="/">
                    House Rental
                </a>
            @endif
        @else
            <a class="navbar-brand fw-bold" href="/">
                House Rental
            </a>
        @endauth

        {{-- Toggle (mobile) --}}
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNavbar">

            {{-- LEFT MENU --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                {{-- ADMIN --}}
                @auth
                @if(auth()->user()->role === 'admin')
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/admin/dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/admin/transactions">
                            Transactions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/admin/users">
                            Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link "
                        href="/admin/login-audit">
                            Login Logs
                        </a>
                    </li>
                @endif

                {{-- AGENT --}}
                @if(auth()->user()->role === 'agent')
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/agent/dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/agent/properties">
                            My Properties
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/agent/rental-requests">
                            Rental Requests
                        </a>
                    </li>
                @endif

                {{-- TENANT --}}
                @if(auth()->user()->role === 'tenant')
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/tenant/dashboard">
                            Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/houses">
                            Browse Houses
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/tenant/requests">
                            My Rental Requests
                        </a>
                    </li>
                @endif

                @if(in_array(auth()->user()->role, ['tenant', 'agent'], true))
                    <li class="nav-item">
                        <a class="nav-link "
                           href="/messages">
                            Chats
                        </a>
                    </li>
                @endif
                @endauth

                {{-- GUEST --}}
                @guest
                    <li class="nav-item">
                        <a class="nav-link " href="/houses">
                            Browse Houses
                        </a>
                    </li>
                @endguest

            </ul>

            {{-- RIGHT MENU --}}
            <ul class="navbar-nav ms-auto">

                @auth
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-inline-flex align-items-center gap-2"
                       href="#"
                       role="button"
                       data-bs-toggle="dropdown">
                        <img src="{{ asset('icons/profile-icon.svg') }}"
                             alt=""
                             width="20"
                             height="20"
                             onerror="this.style.display='none'">
                        {{ auth()->user()->name }}
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <a class="dropdown-item" href="/profile">
                                Profile
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
                @else
                    <li class="nav-item">
                        <a class="btn btn-outline-dark btn-sm me-2 btn-hover-green"
                           href="/login">
                            Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-dark btn-sm btn-hover-green"
                           href="/register">
                            Register
                        </a>
                    </li>
                @endauth

            </ul>

        </div>
    </div>
</nav>
