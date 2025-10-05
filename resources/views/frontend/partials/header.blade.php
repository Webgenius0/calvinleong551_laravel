<!-- mobile header -->
<header class="mobile-header-1">
    <div class="container">
        <!-- menu icon -->
        <div class="menu-icon d-inline-flex me-4">
            <button>
                <span></span>
            </button>
        </div>
    </div>
</header>

<!-- desktop header -->
<header class="desktop-header-1 d-flex align-items-start flex-column">

    <!-- logo image -->
    <div class="site-logo">
        <a href="{{ route('home') }}">
            <h1 class="logo-text text-white">ias24<span class="text-primary">x</span>7</h1>
        </a>
    </div>

    <!-- main menu -->
    <nav>
        <ul class="vertical-menu scrollspy">
            <li class="active"><a href="#home"><i class="icon-home"></i>Home</a></li>
            <li><a href="#about"><i class="icon-user-following"></i>About</a></li>
            <li><a href="#services"><i class="icon-briefcase"></i>Services</a></li>
            <li><a href="#experience"><i class="icon-graduation"></i>Experience</a></li>
            <li><a href="#works"><i class="icon-layers"></i>Works</a></li>
            <li><a href="#blog"><i class="icon-note"></i>Blog</a></li>
            <li><a href="#contact"><i class="icon-bubbles"></i>Contact</a></li>
        </ul>
    </nav>

    <!-- site footer -->
    <div class="footer">
        <!-- copyright text -->
        <span class="copyright">{{ settings()->copyright ?? date('Y') }}</span>
    </div>

</header>