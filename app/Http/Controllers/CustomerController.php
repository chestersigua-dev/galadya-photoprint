<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display searchable client database.
     */
    public function index(Request $request): View
    {
        $search = trim($request->get('search', ''));
        $sort = $request->get('sort', 'created_at');
        $direction = $request->get('direction', 'desc');

        $customers = Customer::with(['transactions.package'])
            ->when($search, function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('contact_number', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            })
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers', 'search', 'sort', 'direction'));
    }

    /**
     * Store newly created customer via standard request.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        return redirect()->route('clients.index')
            ->with('success', 'Client profile successfully created.');
    }

    /**
     * Show customer details (JSON for modal drawer or AJAX view).
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load(['transactions.package', 'transactions.addons.packageAddon', 'transactions.creator']);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $customer->id,
                'full_name' => $customer->full_name,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'contact_number' => $customer->contact_number,
                'address' => $customer->address,
                'notes' => $customer->notes,
                'total_sessions_count' => $customer->total_sessions_count,
                'lifetime_spend' => $customer->formatted_lifetime_spend,
                'transactions' => $customer->transactions->map(fn($t) => [
                    'id' => $t->id,
                    'code' => $t->transaction_code,
                    'package_title' => $t->package->title,
                    'date' => $t->formatted_date,
                    'grand_total' => $t->formatted_grand_total,
                    'payment_method' => strtoupper($t->payment_method),
                    'payment_status' => $t->payment_status,
                ]),
            ],
        ]);
    }

    /**
     * Update customer profile.
     */
    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()->route('clients.index')
            ->with('success', 'Client profile updated.');
    }

    /**
     * Soft delete customer profile.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Client profile deleted.');
    }
}

