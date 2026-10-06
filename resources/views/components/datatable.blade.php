@props(['title'])

<div class="card" data-datatable>
    <div class="card-header">
        <h3 class="card-title">{{ $title }}</h3>
    </div>
    <div class="card-body border-bottom py-3">
        <div class="d-flex">
            <div class="text-secondary">
                Show
                <div class="mx-2 d-inline-block">
                    <input type="text" class="form-control form-control-sm" value="8" size="3" aria-label="Jumlah data" data-dt-length>
                </div>
                entries
            </div>
            <div class="ms-auto text-secondary">
                Search:
                <div class="ms-2 d-inline-block">
                    <input type="text" class="form-control form-control-sm" aria-label="Cari" data-dt-search>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table card-table table-vcenter datatable">
            {{ $slot }}
        </table>
    </div>
    <div class="card-footer d-flex align-items-center">
        <p class="m-0 text-secondary">Showing <span data-dt-from>0</span> to <span data-dt-to>0</span> of <span data-dt-total>0</span> entries</p>
        <ul class="pagination m-0 ms-auto" data-dt-pagination></ul>
    </div>
</div>
