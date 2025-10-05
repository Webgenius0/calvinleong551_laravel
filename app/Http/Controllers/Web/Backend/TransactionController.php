<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    public function index(Request $request, $user_id = null)
    {
        $user_id = $user_id ?? auth('web')->user()->id;

        if ($request->ajax()) {
            $data = Transaction::with(['user'])
                ->when($user_id, function ($query, $user_id) {
                    return $query->where('user_id', $user_id);
                })
                ->orderBy('id', 'desc')
                ->get();

            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('title', function ($data) {
                    return $data->title ? Str::limit($data->title, 20) : '-';
                })
                ->addColumn('user', function ($data) {
                    return "<a href='" . route('admin.users.show', $data->user->id) . "'>" . $data->user->name . "</a>";
                })
                ->addColumn('action', function ($data) {
                    return '<div class="btn-group btn-group-sm" role="group" aria-label="Basic example">

                                <a href="#" type="button" onclick="goToOpen(' . $data->id . ')" class="btn btn-info fs-14 text-white delete-icn" title="Delete">
                                    <i class="fe fe-eye"></i>
                                </a>

                            </div>';
                })
                ->rawColumns(['title', 'user', 'action'])
                ->make();
        }

        $user = User::find($user_id);      

        return view("backend.layouts.transaction.index", compact('user'));
    }

    public function show($id)
    {
        $transaction = Transaction::with(['user'])->find($id);

        if (!$transaction) {
            return redirect()->route('admin.transaction.index')->with('error', 'Transaction not found');
        }

        return view("backend.layouts.transaction.show", compact('transaction'));
    }
}
