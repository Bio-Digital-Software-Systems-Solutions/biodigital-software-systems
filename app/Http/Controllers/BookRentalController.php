<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookRental;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class BookRentalController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view books')->only(['index', 'show']);
        $this->middleware('can:manage library')->only(['update', 'destroy']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = BookRental::with(['book.category', 'user', 'library'])
            ->where('user_id', Auth::id());

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $rentals = $query->latest()->paginate(10);

        return Inertia::render('BookRentals/Index', [
            'rentals' => $rentals,
            'filters' => [
                'status' => $request->status,
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(BookRental $rental): Response
    {
        $this->authorize('view', $rental);

        $rental->load(['book.category', 'user', 'library']);

        return Inertia::render('BookRentals/Show', [
            'rental' => $rental,
        ]);
    }

    /**
     * Return a rented book
     */
    public function returnBook(BookRental $rental): RedirectResponse
    {
        $this->authorize('update', $rental);

        if ($rental->status !== 'active') {
            return back()->with('error', 'Cette location n\'est pas active.');
        }

        $returnDate = now();
        $lateFee = 0;

        if ($returnDate->isAfter($rental->due_date)) {
            $daysLate = $returnDate->diffInDays($rental->due_date);
            $lateFee = $daysLate * 2;
        }

        $rental->update([
            'return_date' => $returnDate,
            'late_fee' => $lateFee,
            'status' => 'returned',
        ]);

        $message = 'Livre retourné avec succès.';
        if ($lateFee > 0) {
            $message .= " Frais de retard: {$lateFee}€";
        }

        return back()->with('message', $message);
    }

    /**
     * Extend rental period
     */
    public function extendRental(Request $request, BookRental $rental): RedirectResponse
    {
        $this->authorize('update', $rental);

        if ($rental->status !== 'active') {
            return back()->with('error', 'Cette location n\'est pas active.');
        }

        $validated = $request->validate([
            'extension_days' => 'required|integer|min:1|max:14',
        ]);

        $newDueDate = $rental->due_date->copy()->addDays($validated['extension_days']);
        $extensionFee = $rental->book->rental_price * $validated['extension_days'];

        $rental->update([
            'due_date' => $newDueDate,
            'rental_fee' => $rental->rental_fee + $extensionFee,
        ]);

        return back()->with('message', "Location prolongée jusqu'au {$newDueDate->format('d/m/Y')}. Frais supplémentaires: {$extensionFee}€");
    }

    /**
     * Admin: List all rentals
     */
    public function adminIndex(Request $request): Response
    {
        $this->authorize('manage library');

        $query = BookRental::with(['book.category', 'user', 'library']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->whereHas('book', function ($q) use ($request) {
                $q->where('title', 'like', "%{$request->search}%")
                    ->orWhere('author', 'like', "%{$request->search}%");
            })->orWhereHas('user', function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                    ->orWhere('last_name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $rentals = $query->latest()->paginate(20);
        $overdueRentals = BookRental::where('due_date', '<', now())
            ->where('status', 'active')
            ->count();

        return Inertia::render('Admin/BookRentals/Index', [
            'rentals' => $rentals,
            'overdueRentals' => $overdueRentals,
            'filters' => [
                'status' => $request->status,
                'search' => $request->search,
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookRental $rental): RedirectResponse
    {
        $this->authorize('manage library');

        $rental->delete();

        return back()->with('message', 'Location supprimée avec succès.');
    }
}
