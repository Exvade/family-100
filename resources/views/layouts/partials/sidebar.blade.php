      <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebar-menu" aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <h1 class="navbar-brand navbar-brand-autodark brand-text">
            <a href="{{ url('/') }}">{{ config('app.name') }}</a>
          </h1>
          <div class="collapse navbar-collapse" id="sidebar-menu">
            <ul class="navbar-nav pt-lg-3">
              <li class="nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
                <a class="nav-link" href="{{ url('/') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l-2 0l9 -9l9 9l-2 0" /><path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7" /><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6" /></svg>
                  </span>
                  <span class="nav-link-title">
                    Home
                  </span>
                </a>
              </li>
              <li class="nav-item dropdown {{ request()->routeIs('family-100.*') ? 'active' : '' }}">
                <a class="nav-link dropdown-toggle {{ request()->routeIs('family-100.*') ? 'show' : '' }}" href="#navbar-family-100" data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="{{ request()->routeIs('family-100.*') ? 'true' : 'false' }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                  </span>
                  <span class="nav-link-title">
                    Family 100
                  </span>
                </a>
                <div class="dropdown-menu {{ request()->routeIs('family-100.*') ? 'show' : '' }}">
                  <a class="dropdown-item {{ request()->routeIs('family-100.questions.*', 'family-100.answers.*') ? 'active' : '' }}" href="{{ route('family-100.questions.index') }}">
                    Pertanyaan
                  </a>
                  <a class="dropdown-item {{ request()->routeIs('family-100.gifts.*') ? 'active' : '' }}" href="{{ route('family-100.gifts.index') }}">
                    Hadiah Kuis
                  </a>
                  <a class="dropdown-item {{ request()->routeIs('family-100.settings.*') ? 'active' : '' }}" href="{{ route('family-100.settings.edit') }}">
                    Pengaturan
                  </a>
                </div>
              </li>
              <li class="nav-item {{ request()->routeIs('doorprize*') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('doorprize') }}">
                  <span class="nav-link-icon d-md-none d-lg-inline-block">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 8m0 1a1 1 0 0 1 1 -1h16a1 1 0 0 1 1 1v2a1 1 0 0 1 -1 1h-16a1 1 0 0 1 -1 -1z" /><path d="M12 8l0 13" /><path d="M19 12v7a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-7" /><path d="M7.5 8a2.5 2.5 0 0 1 0 -5a4.8 8 0 0 1 4.5 5a4.8 8 0 0 1 4.5 -5a2.5 2.5 0 0 1 0 5" /></svg>
                  </span>
                  <span class="nav-link-title">
                    Doorprize
                  </span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </aside>
