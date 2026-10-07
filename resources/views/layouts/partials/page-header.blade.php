<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                @hasSection('page-pretitle')
                    <div class="page-pretitle">@yield('page-pretitle')</div>
                @endif
                <h2 class="page-title">@yield('page-title')</h2>
            </div>
            @hasSection('page-actions')
                <div class="col-12 col-md-auto ms-md-auto d-print-none page-actions">
                    <div class="btn-list">@yield('page-actions')</div>
                </div>
            @endif
        </div>
    </div>
</div>
