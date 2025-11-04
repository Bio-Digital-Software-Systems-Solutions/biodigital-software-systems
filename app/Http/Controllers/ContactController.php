<?php

namespace App\Http\Controllers;

use App\Mail\ContactSubmitted;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth')->except(['create', 'store']);
        $this->middleware('can:manage contacts')->except(['create', 'store']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $contacts = Contact::with('assignedTo')
            ->orderByRaw("CASE WHEN status = 'new' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Contacts/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $contact = Contact::create($validated);

        // Send notification email to admins with 'manage contacts' permission
        $admins = User::permission('manage contacts')->get();

        if ($admins->isNotEmpty()) {
            foreach ($admins as $admin) {
                Mail::to($admin->email)->send(new ContactSubmitted($contact));
            }
        } else {
            // Fallback to default admin email if no users with permission found
            $defaultEmail = config('mail.from.contact');
            if ($defaultEmail) {
                Mail::to($defaultEmail)->send(new ContactSubmitted($contact));
            }
        }

        return redirect()->back()->with('success', 'Votre message a été envoyé avec succès. Nous vous répondrons bientôt.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Contact $contact): Response
    {
        $contact->load('assignedTo');
        $contact->markAsRead();

        return Inertia::render('Contacts/Show', [
            'contact' => $contact,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Contact $contact): Response
    {
        $contact->load('assignedTo');

        return Inertia::render('Contacts/Edit', [
            'contact' => $contact,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contact $contact): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:new,in_progress,resolved,closed',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $contact->update($validated);

        return redirect()->route('contacts.index')->with('success', 'Contact mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('success', 'Contact supprimé avec succès.');
    }
}
