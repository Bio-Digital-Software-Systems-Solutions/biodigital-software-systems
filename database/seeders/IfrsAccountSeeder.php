<?php

namespace Database\Seeders;

use App\Models\Accounting\IfrsAccount;
use App\Models\Accounting\IfrsAccountClass;
use Illuminate\Database\Seeder;

class IfrsAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedClasses();
        $this->seedAccounts();
    }

    private function seedClasses(): void
    {
        $classes = [
            ['class_number' => 1, 'name' => 'Assets', 'category' => 'assets', 'description' => 'Current and non-current assets'],
            ['class_number' => 2, 'name' => 'Liabilities', 'category' => 'liabilities', 'description' => 'Current and non-current liabilities'],
            ['class_number' => 3, 'name' => 'Equity', 'category' => 'equity', 'description' => 'Share capital, reserves, retained earnings'],
            ['class_number' => 4, 'name' => 'Revenue', 'category' => 'revenue', 'description' => 'Operating and other income'],
            ['class_number' => 5, 'name' => 'Expenses', 'category' => 'expenses', 'description' => 'Operating, financial and other expenses'],
        ];

        foreach ($classes as $index => $class) {
            IfrsAccountClass::firstOrCreate(
                ['class_number' => $class['class_number']],
                array_merge($class, ['sort_order' => $index])
            );
        }
    }

    private function seedAccounts(): void
    {
        $accounts = [
            // Assets
            ['class' => 1, 'number' => '1100', 'name' => 'Cash and Cash Equivalents', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 1, 'number' => '1200', 'name' => 'Trade Receivables', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 2],
            ['class' => 1, 'number' => '1300', 'name' => 'Inventories', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 3],
            ['class' => 1, 'number' => '1400', 'name' => 'Prepayments', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 4],
            ['class' => 1, 'number' => '1500', 'name' => 'Property, Plant and Equipment', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 5],
            ['class' => 1, 'number' => '1600', 'name' => 'Intangible Assets', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 6],
            ['class' => 1, 'number' => '1700', 'name' => 'Investment Property', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 7],
            ['class' => 1, 'number' => '1800', 'name' => 'Financial Assets', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 8],
            ['class' => 1, 'number' => '1900', 'name' => 'Right-of-Use Assets', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 9],

            // Liabilities
            ['class' => 2, 'number' => '2100', 'name' => 'Trade Payables', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 2, 'number' => '2200', 'name' => 'Short-term Borrowings', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 2],
            ['class' => 2, 'number' => '2300', 'name' => 'Current Tax Liabilities', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 3],
            ['class' => 2, 'number' => '2400', 'name' => 'Provisions', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 4],
            ['class' => 2, 'number' => '2500', 'name' => 'Long-term Borrowings', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 5],
            ['class' => 2, 'number' => '2600', 'name' => 'Deferred Tax Liabilities', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 6],
            ['class' => 2, 'number' => '2700', 'name' => 'Lease Liabilities', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 7],
            ['class' => 2, 'number' => '2800', 'name' => 'Employee Benefits', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 8],

            // Equity
            ['class' => 3, 'number' => '3100', 'name' => 'Share Capital', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 3, 'number' => '3200', 'name' => 'Share Premium', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 2],
            ['class' => 3, 'number' => '3300', 'name' => 'Retained Earnings', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 3],
            ['class' => 3, 'number' => '3400', 'name' => 'Other Reserves', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 4],
            ['class' => 3, 'number' => '3500', 'name' => 'Non-controlling Interests', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 5],

            // Revenue
            ['class' => 4, 'number' => '4100', 'name' => 'Revenue from Contracts with Customers', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 4, 'number' => '4200', 'name' => 'Other Operating Income', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 2],
            ['class' => 4, 'number' => '4300', 'name' => 'Finance Income', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 3],

            // Expenses
            ['class' => 5, 'number' => '5100', 'name' => 'Cost of Sales', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 5, 'number' => '5200', 'name' => 'Administrative Expenses', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 2],
            ['class' => 5, 'number' => '5300', 'name' => 'Selling and Distribution Expenses', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 3],
            ['class' => 5, 'number' => '5400', 'name' => 'Finance Costs', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 4],
            ['class' => 5, 'number' => '5500', 'name' => 'Depreciation and Amortisation', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 5],
            ['class' => 5, 'number' => '5600', 'name' => 'Impairment Losses', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 6],
            ['class' => 5, 'number' => '5700', 'name' => 'Employee Benefits Expense', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 7],
            ['class' => 5, 'number' => '5800', 'name' => 'Income Tax Expense', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 8],
        ];

        foreach ($accounts as $accountData) {
            $class = IfrsAccountClass::where('class_number', $accountData['class'])->first();
            if (! $class) {
                continue;
            }

            $parent = null;
            if (! empty($accountData['parent'])) {
                $parent = IfrsAccount::where('account_number', $accountData['parent'])->first();
            }

            IfrsAccount::firstOrCreate(
                ['account_number' => $accountData['number']],
                [
                    'name' => $accountData['name'],
                    'class_id' => $class->id,
                    'parent_id' => $parent?->id,
                    'level' => $accountData['level'],
                    'normal_balance' => $accountData['balance'],
                    'sort_order' => $accountData['sort'],
                ]
            );
        }
    }
}
