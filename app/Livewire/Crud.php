<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class Crud extends Component
{
    use WithPagination;
    protected $paginationTheme = 'bootstrap';

    public $search = '';
    public $statusFilter = 'all';
    public $perPage = 10;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function toggleStatus($id)
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                session()->flash('error', 'User not found.');
                return;
            }

            // Check if user is trying to modify themselves
            if ($user->id == auth('web')->id()) {
                session()->flash('error', 'You cannot modify your own status.');
                return;
            }

            $oldStatus = $user->status;
            $user->status = ($user->status === 'active') ? 'inactive' : 'active';
            $user->save();

            $statusText = ($user->status === 'active') ? 'activated' : 'deactivated';
            
            Log::info("User status changed: {$user->name} (ID: {$id}) {$statusText}");
            session()->flash('message', "User '{$user->name}' has been {$statusText}.");
            
            $this->dispatch('status-updated', [
                'id' => $id,
                'name' => $user->name,
                'status' => $user->status
            ]);
            
        } catch (\Exception $e) {
            Log::error("Exception during status toggle: " . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'An error occurred while updating the status: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            // Log the attempt
            Log::info("Attempting to delete user with ID: {$id}");
            
            // Validate ID
            if (!is_numeric($id) || $id <= 0) {
                Log::error("Invalid user ID: {$id}");
                session()->flash('error', 'Invalid user ID.');
                return;
            }

            // Find user with additional checks
            $user = User::find($id);
            
            if (!$user) {
                Log::error("User not found with ID: {$id}");
                session()->flash('error', 'User not found.');
                return;
            }

            // Check if user is trying to delete themselves
            if ($user->id == auth('web')->id()) {
                Log::error("User {$id} attempted to delete themselves");
                session()->flash('error', 'You cannot delete yourself.');
                return;
            }

            $userName = $user->name;
            $deleted = $user->delete();
            
            if ($deleted) {
                Log::info("Successfully deleted user: {$userName} (ID: {$id})");
                session()->flash('message', "User '{$userName}' deleted successfully.");
                
                // Reset pagination to avoid empty pages
                $this->resetPage();
                
                // Dispatch event for JS listening
                $this->dispatch('user-deleted', ['id' => $id, 'name' => $userName]);
            } else {
                Log::error("Failed to delete user: {$userName} (ID: {$id})");
                session()->flash('error', 'Failed to delete user. Please try again.');
            }
            
        } catch (\Exception $e) {
            Log::error("Exception during user deletion: " . $e->getMessage(), [
                'id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'An error occurred while deleting the user: ' . $e->getMessage());
        }
    }

    // Add method to refresh component manually
    public function refreshComponent()
    {
        $this->resetPage();
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $query = User::where('id', '!=', auth('web')->id());

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('slug', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if ($this->statusFilter === 'active') {
            $query->where('status', 'active');
        } elseif ($this->statusFilter === 'inactive') {
            $query->where('status', 'inactive');
        }

        $users = $query->orderBy('id', 'desc')->paginate($this->perPage);
        
        return view('livewire.crud', compact('users'));
    }
}