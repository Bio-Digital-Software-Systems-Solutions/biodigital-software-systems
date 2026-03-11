<?php

namespace App\Http\Controllers;

use App\Models\Accounting\AccountingSystem;
use App\Models\Accounting\IfrsAccountClass;
use App\Models\Accounting\OhadaAccountClass;
use App\Models\Accounting\OhadaFinancialStatement;
use App\Models\Accounting\PcgAccountClass;
use Inertia\Inertia;
use Inertia\Response;

class AccountingController extends Controller
{
    public function index(): Response
    {
        $ohadaClasses = OhadaAccountClass::query()
            ->with(['accounts' => fn ($q) => $q->whereNull('parent_id')->with('children.children')->orderBy('sort_order')])
            ->orderBy('class_number')
            ->get();

        $pcgClasses = PcgAccountClass::query()
            ->with(['accounts' => fn ($q) => $q->whereNull('parent_id')->with('children')->orderBy('sort_order')])
            ->orderBy('class_number')
            ->get();

        $ifrsClasses = IfrsAccountClass::query()
            ->with(['accounts' => fn ($q) => $q->whereNull('parent_id')->with('children')->orderBy('sort_order')])
            ->orderBy('class_number')
            ->get();

        $accountingSystems = AccountingSystem::query()
            ->with('financialStatements')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $financialStatements = OhadaFinancialStatement::query()
            ->with('accountingSystem')
            ->orderBy('sort_order')
            ->get();

        $stats = [
            'totalOhadaAccounts' => OhadaAccountClass::withCount('accounts')->get()->sum('accounts_count'),
            'totalPcgAccounts' => PcgAccountClass::withCount('accounts')->get()->sum('accounts_count'),
            'totalIfrsAccounts' => IfrsAccountClass::withCount('accounts')->get()->sum('accounts_count'),
            'totalSystems' => AccountingSystem::where('is_active', true)->count(),
        ];

        return Inertia::render('Accounting/Index', [
            'ohadaClasses' => $ohadaClasses,
            'pcgClasses' => $pcgClasses,
            'ifrsClasses' => $ifrsClasses,
            'accountingSystems' => $accountingSystems,
            'financialStatements' => $financialStatements,
            'stats' => $stats,
        ]);
    }
}
