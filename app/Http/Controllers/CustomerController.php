<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Display all customers

    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $customers = Customer::query()
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    // Display the form for creating a customer
    public function create()
    {
        return view('customers.create');
    }

    // Save a new customer
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        $customerAccount = ! empty($validated['email'])
            ? User::where('email', $validated['email'])
                ->where('role', 'customer')
                ->whereDoesntHave('customer')
                ->first()
            : null;

        Customer::create([
            ...$validated,
            'user_id' => $customerAccount?->id,
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer added successfully.');
    }

    // Display a customer's details
    public function show(Customer $customer)
    {
        return view('customers.show', compact('customer'));
    }

    // Display the form for editing a customer
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    // Update a customer
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'nullable|string',
        ]);

        $customer->update($validated);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    // Delete a customer
    public function destroy(Customer $customer)
    {
        if ($customer->invoices()->exists()) {
            return redirect()
                ->route('customers.index')
                ->withErrors([
                    'customer' => 'Customers with invoices cannot be deleted. Archive or retain this record instead.',
                ]);
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }
}
