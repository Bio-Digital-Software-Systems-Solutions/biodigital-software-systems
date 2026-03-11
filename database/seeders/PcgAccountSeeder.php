<?php

namespace Database\Seeders;

use App\Models\Accounting\PcgAccount;
use App\Models\Accounting\PcgAccountClass;
use Illuminate\Database\Seeder;

class PcgAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedClasses();
        $this->seedAccounts();
    }

    private function seedClasses(): void
    {
        $classes = [
            ['class_number' => 1, 'name' => 'Comptes de capitaux', 'category' => 'bilan', 'description' => 'Capital, réserves, résultat, provisions, emprunts'],
            ['class_number' => 2, 'name' => 'Comptes d\'immobilisations', 'category' => 'bilan', 'description' => 'Immobilisations incorporelles, corporelles et financières'],
            ['class_number' => 3, 'name' => 'Comptes de stocks et en-cours', 'category' => 'bilan', 'description' => 'Matières premières, marchandises, produits'],
            ['class_number' => 4, 'name' => 'Comptes de tiers', 'category' => 'bilan', 'description' => 'Fournisseurs, clients, personnel, État'],
            ['class_number' => 5, 'name' => 'Comptes financiers', 'category' => 'bilan', 'description' => 'Valeurs mobilières, banques, caisse'],
            ['class_number' => 6, 'name' => 'Comptes de charges', 'category' => 'gestion', 'description' => 'Achats, services extérieurs, impôts, charges de personnel'],
            ['class_number' => 7, 'name' => 'Comptes de produits', 'category' => 'gestion', 'description' => 'Ventes, production, produits financiers'],
            ['class_number' => 8, 'name' => 'Comptes spéciaux', 'category' => 'gestion', 'description' => 'Engagements, résultat en instance'],
        ];

        foreach ($classes as $index => $class) {
            PcgAccountClass::firstOrCreate(
                ['class_number' => $class['class_number']],
                array_merge($class, ['sort_order' => $index])
            );
        }
    }

    private function seedAccounts(): void
    {
        $accounts = [
            // Classe 1
            ['class' => 1, 'number' => '10', 'name' => 'Capital et réserves', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 1, 'number' => '101', 'name' => 'Capital', 'parent' => '10', 'level' => 2, 'balance' => 'credit', 'sort' => 2],
            ['class' => 1, 'number' => '106', 'name' => 'Réserves', 'parent' => '10', 'level' => 2, 'balance' => 'credit', 'sort' => 3],
            ['class' => 1, 'number' => '11', 'name' => 'Report à nouveau', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 10],
            ['class' => 1, 'number' => '12', 'name' => 'Résultat de l\'exercice', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 20],
            ['class' => 1, 'number' => '13', 'name' => 'Subventions d\'investissement', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 30],
            ['class' => 1, 'number' => '15', 'name' => 'Provisions pour risques et charges', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 50],
            ['class' => 1, 'number' => '16', 'name' => 'Emprunts et dettes assimilées', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 60],

            // Classe 2
            ['class' => 2, 'number' => '20', 'name' => 'Immobilisations incorporelles', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 2, 'number' => '21', 'name' => 'Immobilisations corporelles', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 2, 'number' => '26', 'name' => 'Participations et créances rattachées', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 60],
            ['class' => 2, 'number' => '27', 'name' => 'Autres immobilisations financières', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 70],
            ['class' => 2, 'number' => '28', 'name' => 'Amortissements des immobilisations', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 80],
            ['class' => 2, 'number' => '29', 'name' => 'Dépréciations des immobilisations', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 90],

            // Classe 3
            ['class' => 3, 'number' => '31', 'name' => 'Matières premières', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 3, 'number' => '37', 'name' => 'Stocks de marchandises', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 70],
            ['class' => 3, 'number' => '39', 'name' => 'Dépréciations des stocks', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 90],

            // Classe 4
            ['class' => 4, 'number' => '40', 'name' => 'Fournisseurs et comptes rattachés', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 4, 'number' => '41', 'name' => 'Clients et comptes rattachés', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 4, 'number' => '42', 'name' => 'Personnel et comptes rattachés', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 20],
            ['class' => 4, 'number' => '43', 'name' => 'Sécurité sociale et autres organismes sociaux', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 30],
            ['class' => 4, 'number' => '44', 'name' => 'État et autres collectivités publiques', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 40],

            // Classe 5
            ['class' => 5, 'number' => '50', 'name' => 'Valeurs mobilières de placement', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 5, 'number' => '51', 'name' => 'Banques, établissements financiers', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 5, 'number' => '53', 'name' => 'Caisse', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 30],

            // Classe 6
            ['class' => 6, 'number' => '60', 'name' => 'Achats', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 6, 'number' => '61', 'name' => 'Services extérieurs', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 6, 'number' => '62', 'name' => 'Autres services extérieurs', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 20],
            ['class' => 6, 'number' => '63', 'name' => 'Impôts, taxes et versements assimilés', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 30],
            ['class' => 6, 'number' => '64', 'name' => 'Charges de personnel', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 40],
            ['class' => 6, 'number' => '66', 'name' => 'Charges financières', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 60],
            ['class' => 6, 'number' => '67', 'name' => 'Charges exceptionnelles', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 70],
            ['class' => 6, 'number' => '68', 'name' => 'Dotations aux amortissements et provisions', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 80],

            // Classe 7
            ['class' => 7, 'number' => '70', 'name' => 'Ventes de produits fabriqués et marchandises', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 7, 'number' => '71', 'name' => 'Production stockée', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 10],
            ['class' => 7, 'number' => '72', 'name' => 'Production immobilisée', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 20],
            ['class' => 7, 'number' => '74', 'name' => 'Subventions d\'exploitation', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 40],
            ['class' => 7, 'number' => '76', 'name' => 'Produits financiers', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 60],
            ['class' => 7, 'number' => '77', 'name' => 'Produits exceptionnels', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 70],
            ['class' => 7, 'number' => '78', 'name' => 'Reprises sur amortissements et provisions', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 80],
        ];

        foreach ($accounts as $accountData) {
            $class = PcgAccountClass::where('class_number', $accountData['class'])->first();
            if (! $class) {
                continue;
            }

            $parent = null;
            if (! empty($accountData['parent'])) {
                $parent = PcgAccount::where('account_number', $accountData['parent'])->first();
            }

            PcgAccount::firstOrCreate(
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
