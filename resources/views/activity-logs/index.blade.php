@extends('layouts.admin')

@section('title', 'Activity Log')
@section('page_title', 'Activity Log')
@section('breadcrumb', 'Home / Activity Log')

@section('content')
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="log_name" class="form-label small text-muted mb-1">Channel</label>
                    <select name="log_name" id="log_name" class="form-select">
                        <option value="">All Channels</option>
                        @foreach($logNames as $name)
                            <option value="{{ $name }}" {{ request('log_name') == $name ? 'selected' : '' }}>
                                {{ ucfirst($name) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="event" class="form-label small text-muted mb-1">Event</label>
                    <select name="event" id="event" class="form-select">
                        <option value="">All Events</option>
                        <option value="created" {{ request('event') == 'created' ? 'selected' : '' }}>Created</option>
                        <option value="updated" {{ request('event') == 'updated' ? 'selected' : '' }}>Updated</option>
                        <option value="deleted" {{ request('event') == 'deleted' ? 'selected' : '' }}>Deleted</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label small text-muted mb-1">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label small text-muted mb-1">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-outline-secondary">
                        <i class="fas fa-search me-1"></i> Apply
                    </button>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity log table -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-history me-2 text-primary"></i>Audit Trail
        </div>
        <div class="card-body">
            <div class="table-responsive">
                {{ $dataTable->table(['class' => 'table table-striped table-hover w-100']) }}
            </div>
        </div>
    </div>
    <!-- Activity detail modal -->
    <div class="modal fade" id="activityDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-history me-2"></i>Activity Detail <span id="al-id" class="text-muted"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted mb-2">General</div>
                                <dl class="row mb-0 small">
                                    <dt class="col-5">Description</dt><dd class="col-7" id="al-description"></dd>
                                    <dt class="col-5">Channel</dt><dd class="col-7" id="al-logname"></dd>
                                    <dt class="col-5">Event</dt><dd class="col-7" id="al-event"></dd>
                                    <dt class="col-5">Date</dt><dd class="col-7" id="al-created"></dd>
                                    <dt class="col-5">Batch UUID</dt><dd class="col-7 text-break" id="al-batch"></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded p-3 h-100">
                                <div class="small text-muted mb-2">Actor &amp; Subject</div>
                                <dl class="row mb-0 small">
                                    <dt class="col-5">Performed By</dt><dd class="col-7" id="al-causer"></dd>
                                    <dt class="col-5">Causer Type</dt><dd class="col-7" id="al-causer-type"></dd>
                                    <dt class="col-5">Subject</dt><dd class="col-7" id="al-subject"></dd>
                                    <dt class="col-5">Subject Type</dt><dd class="col-7" id="al-subject-type"></dd>
                                    <dt class="col-5">Updated</dt><dd class="col-7" id="al-updated"></dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <!-- Changed attributes -->
                    <div id="al-changes-wrap" class="d-none">
                        <div class="small text-muted mb-2">Changes (old &rarr; new)</div>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered small mb-0">
                                <thead class="table-light">
                                    <tr><th style="width:25%">Field</th><th style="width:37.5%">Old Value</th><th>New Value</th></tr>
                                </thead>
                                <tbody id="al-changes"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Extra properties -->
                    <div id="al-props-wrap" class="d-none mt-3">
                        <div class="small text-muted mb-2">Properties</div>
                        <pre class="bg-light border rounded p-2 small mb-0" id="al-props" style="max-height:250px;overflow:auto;"></pre>
                    </div>

                    <div id="al-empty" class="d-none text-muted small">No extra data recorded for this entry.</div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
{{ $dataTable->scripts() }}
<script>
let activityModal;

function esc(value) {
    if (value === null || value === undefined) return '—';
    if (typeof value === 'object') value = JSON.stringify(value);
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function showActivityDetail(id) {
    activityModal = activityModal || new bootstrap.Modal(document.getElementById('activityDetailModal'));

    fetch(`/activity-logs/${id}`, { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            document.getElementById('al-id').textContent = '#' + d.id;
            document.getElementById('al-description').textContent = d.description;
            document.getElementById('al-logname').textContent = d.log_name;
            document.getElementById('al-event').textContent = d.event || 'action';
            document.getElementById('al-created').textContent = d.created_at;
            document.getElementById('al-updated').textContent = d.updated_at;
            document.getElementById('al-batch').textContent = d.batch_uuid || '—';
            document.getElementById('al-causer').textContent = d.causer_name
                ? d.causer_name + (d.causer_email ? ' (' + d.causer_email + ')' : '')
                : 'System';
            document.getElementById('al-causer-type').textContent = d.causer_type || '—';
            document.getElementById('al-subject').textContent = d.subject_label || (d.subject_id ? '#' + d.subject_id : '—');
            document.getElementById('al-subject-type').textContent = d.subject_type || '—';

            const props = d.properties || {};
            const attrs = props.attributes || {};
            const old = props.old || {};
            const changesBody = document.getElementById('al-changes');
            const changesWrap = document.getElementById('al-changes-wrap');
            const propsWrap = document.getElementById('al-props-wrap');
            const empty = document.getElementById('al-empty');
            changesBody.innerHTML = '';

            // Fields that changed (attributes / old) rendered as a diff table
            const changedKeys = [...new Set([...Object.keys(attrs), ...Object.keys(old)])];
            if (changedKeys.length) {
                changedKeys.forEach(key => {
                    changesBody.innerHTML += `<tr><td class="fw-semibold">${esc(key)}</td><td>${esc(old[key] ?? '—')}</td><td>${esc(attrs[key] ?? '—')}</td></tr>`;
                });
                changesWrap.classList.remove('d-none');
            } else {
                changesWrap.classList.add('d-none');
            }

            // Any remaining properties shown as raw JSON
            const extra = { ...props };
            delete extra.attributes;
            delete extra.old;
            if (Object.keys(extra).length) {
                document.getElementById('al-props').textContent = JSON.stringify(extra, null, 2);
                propsWrap.classList.remove('d-none');
            } else {
                propsWrap.classList.add('d-none');
            }

            empty.classList.toggle('d-none', changedKeys.length || Object.keys(extra).length);

            activityModal.show();
        })
        .catch(() => showToast('error', 'Failed to load activity details.'));
}
</script>
@endpush
