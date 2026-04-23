<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVisitorRequest;
use App\Http\Requests\UpdateVisitorRequest;
use App\Models\Visitor;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VisitorController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:view visitors')->only(['index', 'show']);
        $this->middleware('can:create visitors')->only(['create', 'store']);
        $this->middleware('can:edit visitors')->only(['edit', 'update']);
        $this->middleware('can:delete visitors')->only(['destroy']);
    }

    public function index()
    {
        $visitors = Visitor::query()
            ->with(['creator'])
            ->withCount('visits')
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('search'), function ($q, $search): void {
                $q->where(function ($query) use ($search): void {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Visitors/Index', [
            'visitors' => $visitors,
            'filters' => request()->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Visitors/Create');
    }

    public function store(StoreVisitorRequest $request)
    {
        $visitor = Visitor::create([
            ...$request->validated(),
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('visitors.show', $visitor)
            ->with('success', 'Visiteur créé avec succès.');
    }

    public function show(Visitor $visitor)
    {
        $visitor->load([
            'visits.visitable',
            'visits.attendances.attendable',
            'visits.integrationProgress.step',
            'visits.suggestion',
            'creator',
        ]);

        return Inertia::render('Visitors/Show', [
            'visitor' => $visitor,
        ]);
    }

    public function edit(Visitor $visitor)
    {
        return Inertia::render('Visitors/Edit', [
            'visitor' => $visitor,
        ]);
    }

    public function update(UpdateVisitorRequest $request, Visitor $visitor)
    {
        $visitor->update($request->validated());

        return redirect()->route('visitors.show', $visitor)
            ->with('success', 'Visiteur mis à jour avec succès.');
    }

    public function destroy(Visitor $visitor)
    {
        $visitor->delete();

        return redirect()->route('visitors.index')
            ->with('success', 'Visiteur supprimé avec succès.');
    }
}
