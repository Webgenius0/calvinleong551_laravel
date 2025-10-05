<div>
    <style>
        .loading-center {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .loading-center img {
            width: 80px;
            height: 80px;
        }

        .search-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }

        .status-toggle {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .status-toggle:hover {
            transform: scale(1.1);
        }

        .table-actions {
            white-space: nowrap;
        }

        .btn-group-sm>.btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }

        .user-avatar {
            transition: transform 0.3s ease;
        }

        .user-avatar:hover {
            transform: scale(1.1);
        }

        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .table th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
        }

        .fade-row {
            opacity: 0.5;
            transition: opacity 0.5s ease;
        }
    </style>

    <!-- Flash Messages -->
    @if (session()->has('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if (session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Search and Filter Section -->
    <div class="search-container">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text"
                        class="form-control"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search by name, email, or slug...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="statusFilter">
                    <option value="all">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" wire:model.live="perPage">
                    <option value="5">5 per page</option>
                    <option value="10">10 per page</option>
                    <option value="25">25 per page</option>
                    <option value="50">50 per page</option>
                </select>
            </div>
            <div class="col-md-3">
                <div class="btn-group w-100">
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearSearch">
                        <i class="fas fa-times"></i> Clear
                    </button>
                    <button type="button" class="btn btn-outline-primary" wire:click="refreshComponent">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Search Results Info -->
        @if(!empty($search) || $statusFilter !== 'all')
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Showing {{ $users->count() }} of {{ $users->total() }} results
                @if(!empty($search))
                for "<strong>{{ $search }}</strong>"
                @endif
                @if($statusFilter !== 'all')
                with status: <strong>{{ ucfirst($statusFilter) }}</strong>
                @endif
            </small>
        </div>
        @endif
    </div>

    <!-- Centered Loading Spinner -->
    <div class="loading-center" wire:loading>
        <img src="{{ asset('default/loader.gif') }}" alt="Loading..." />
        <div class="mt-2">Processing, please wait...</div>
    </div>

    <!-- Users Table -->
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Email</th>
                    <th style="width: 80px;">Avatar</th>
                    <th style="width: 120px;">Status</th>
                    <th style="width: 200px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr id="user-row-{{ $user->id }}" class="align-middle">
                    <td><strong>{{ $user->id }}</strong></td>
                    <td>
                        <div class="fw-medium">{{ $user->name }}</div>
                    </td>
                    <td>
                        <code class="text-muted">{{ $user->slug }}</code>
                    </td>
                    <td>
                        <a href="mailto:{{ $user->email }}" class="text-decoration-none">
                            {{ $user->email }}
                        </a>
                    </td>
                    <td>
                        <img src="{{ $user->avatar ? asset($user->avatar) : asset('default/profile.jpg') }}"
                            alt="Avatar"
                            width="50"
                            height="50"
                            class="rounded-circle user-avatar"
                            style="object-fit: cover;">
                    </td>
                    <td>
                        <button type="button"
                            class="btn btn-sm status-toggle {{ $user->status === 'active' ? 'btn-success' : 'btn-secondary' }}"
                            wire:click="toggleStatus({{ $user->id }})"
                            wire:loading.attr="disabled"
                            wire:target="toggleStatus({{ $user->id }})"
                            title="Click to {{ $user->status === 'active' ? 'deactivate' : 'activate' }} user">
                            <span wire:loading.remove wire:target="toggleStatus({{ $user->id }})">
                                <i class="fas {{ $user->status === 'active' ? 'fa-check' : 'fa-times' }}"></i>
                                {{ $user->status === 'active' ? 'Active' : 'Inactive' }}
                            </span>
                            <span wire:loading wire:target="toggleStatus({{ $user->id }})">
                                <i class="fas fa-spinner fa-spin"></i>
                            </span>
                        </button>
                    </td>
                    <td class="table-actions">
                        <div class="btn-group btn-group-sm">
                            <button type="button"
                                class="btn {{ $user->status === 'active' ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                wire:click="toggleStatus({{ $user->id }})"
                                wire:loading.attr="disabled"
                                wire:target="toggleStatus({{ $user->id }})"
                                title="{{ $user->status === 'active' ? 'Deactivate' : 'Activate' }} user">
                                <span wire:loading.remove wire:target="toggleStatus({{ $user->id }})">
                                    <i class="fas {{ $user->status === 'active' ? 'fa-pause' : 'fa-play' }}"></i>
                                </span>
                                <span wire:loading wire:target="toggleStatus({{ $user->id }})">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>

                            <button type="button"
                                class="btn btn-outline-danger"
                                wire:click="delete({{ $user->id }})"
                                wire:confirm="Are you sure you want to delete '{{ $user->name }}'? This action cannot be undone."
                                wire:loading.attr="disabled"
                                wire:target="delete({{ $user->id }})"
                                data-user-id="{{ $user->id }}"
                                data-user-name="{{ $user->name }}"
                                title="Delete user">
                                <span wire:loading.remove wire:target="delete({{ $user->id }})">
                                    <i class="fas fa-trash"></i>
                                </span>
                                <span wire:loading wire:target="delete({{ $user->id }})">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                            <h5>No users found</h5>
                            <p class="mb-0">
                                @if(!empty($search) || $statusFilter !== 'all')
                                Try adjusting your search criteria or filters.
                                @else
                                No users are available to display.
                                @endif
                            </p>
                            @if(!empty($search) || $statusFilter !== 'all')
                            <button class="btn btn-outline-primary btn-sm mt-2" wire:click="clearSearch">
                                <i class="fas fa-times"></i> Clear Filters
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination with Info -->
    @if($users->hasPages())
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted">
            Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
        </div>
        <div>
            {{ $users->links() }}
        </div>
    </div>
    @endif

    <!-- JavaScript for enhanced UX -->
    <script>
        document.addEventListener('livewire:init', () => {
            // Listen for user deletion events
            Livewire.on('user-deleted', (event) => {
                console.log('User deleted successfully:', event);

                // Add fade effect to deleted row
                const row = document.getElementById(`user-row-${event.id}`);
                if (row) {
                    row.classList.add('fade-row');
                    setTimeout(() => {
                        if (row.parentNode) {
                            row.style.display = 'none';
                        }
                    }, 500);
                }

                // Show toast notification if available
                if (typeof toastr !== 'undefined') {
                    toastr.success(`User "${event.name}" deleted successfully`);
                }
            });

            // Listen for status update events
            Livewire.on('status-updated', (event) => {
                console.log('Status updated:', event);

                // Show toast notification if available
                if (typeof toastr !== 'undefined') {
                    const statusText = (event.status === 'active') ? 'activated' : 'deactivated';
                    toastr.info(`User "${event.name}" ${statusText}`);
                }
            });

            // Add click event listeners for debugging
            document.addEventListener('click', function(e) {
                if (e.target.closest('.status-toggle')) {
                    const btn = e.target.closest('.status-toggle');
                    console.log('Status toggle clicked:', btn.getAttribute('wire:click'));
                }

                if (e.target.closest('[data-user-id]')) {
                    const btn = e.target.closest('[data-user-id]');
                    const userId = btn.getAttribute('data-user-id');
                    const userName = btn.getAttribute('data-user-name');

                    console.log('Action button clicked:', {
                        userId: userId,
                        userName: userName,
                        action: btn.textContent.trim(),
                        timestamp: new Date().toISOString()
                    });
                }
            });
        });

        // Global error handler for Livewire
        document.addEventListener('livewire:error', (event) => {
            console.error('Livewire error:', event.detail);

            // Show user-friendly error message
            if (typeof toastr !== 'undefined') {
                toastr.error('An error occurred. Please try again.');
            } else {
                alert('An error occurred. Please check the console for details.');
            }
        });

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(function(alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    if (bsAlert) {
                        bsAlert.close();
                    }
                });
            }, 5000);
        });
    </script>
</div>