<?php

namespace Database\Seeders;

use App\Models\Accounting\AccountingSystem;
use App\Models\Accounting\OhadaAccount;
use App\Models\Accounting\OhadaAccountClass;
use App\Models\Accounting\OhadaFinancialStatement;
use Illuminate\Database\Seeder;

class OhadaAccountSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAccountClasses();
        $this->seedAccounts();
        $this->seedAccountingSystems();
        $this->seedFinancialStatements();
    }

    private function seedAccountClasses(): void
    {
        $classes = [
            ['class_number' => 1, 'name' => 'Comptes de ressources durables', 'category' => 'bilan', 'description' => 'Capital, réserves, emprunts et dettes à long terme'],
            ['class_number' => 2, 'name' => "Comptes d'actif immobilisé", 'category' => 'bilan', 'description' => 'Immobilisations incorporelles, corporelles et financières'],
            ['class_number' => 3, 'name' => 'Comptes de stocks', 'category' => 'bilan', 'description' => 'Marchandises, matières premières, produits finis'],
            ['class_number' => 4, 'name' => 'Comptes de tiers', 'category' => 'bilan', 'description' => 'Fournisseurs, clients, personnel, État'],
            ['class_number' => 5, 'name' => 'Comptes de trésorerie', 'category' => 'bilan', 'description' => 'Banques, caisses, valeurs mobilières de placement'],
            ['class_number' => 6, 'name' => 'Comptes de charges des activités ordinaires', 'category' => 'gestion', 'description' => 'Achats, services extérieurs, charges de personnel'],
            ['class_number' => 7, 'name' => 'Comptes de produits des activités ordinaires', 'category' => 'gestion', 'description' => 'Ventes, prestations de services, produits financiers'],
            ['class_number' => 8, 'name' => 'Comptes des autres charges et des autres produits', 'category' => 'gestion', 'description' => 'Charges et produits hors activités ordinaires'],
            ['class_number' => 9, 'name' => 'Comptes des engagements hors bilan et comptes de la comptabilité analytique', 'category' => 'hors_bilan', 'description' => 'Engagements donnés et reçus, comptabilité analytique'],
        ];

        foreach ($classes as $index => $class) {
            OhadaAccountClass::firstOrCreate(
                ['class_number' => $class['class_number']],
                array_merge($class, ['sort_order' => $index])
            );
        }
    }

    private function seedAccounts(): void
    {
        $accounts = $this->getOhadaAccounts();

        foreach ($accounts as $accountData) {
            $class = OhadaAccountClass::where('class_number', $accountData['class'])->first();
            if (! $class) {
                continue;
            }

            $parent = null;
            if (! empty($accountData['parent'])) {
                $parent = OhadaAccount::where('account_number', $accountData['parent'])->first();
            }

            OhadaAccount::firstOrCreate(
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

    /**
     * @return array<int, array{class: int, number: string, name: string, parent: string|null, level: int, balance: string, sort: int}>
     */
    private function getOhadaAccounts(): array
    {
        return [
            // Classe 1 - Comptes de ressources durables
            ['class' => 1, 'number' => '10', 'name' => 'Capital', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 1, 'number' => '101', 'name' => 'Capital social', 'parent' => '10', 'level' => 2, 'balance' => 'credit', 'sort' => 2],
            ['class' => 1, 'number' => '1011', 'name' => 'Capital souscrit non appelé', 'parent' => '101', 'level' => 3, 'balance' => 'credit', 'sort' => 3],
            ['class' => 1, 'number' => '1012', 'name' => 'Capital souscrit appelé non versé', 'parent' => '101', 'level' => 3, 'balance' => 'credit', 'sort' => 4],
            ['class' => 1, 'number' => '1013', 'name' => 'Capital souscrit appelé versé', 'parent' => '101', 'level' => 3, 'balance' => 'credit', 'sort' => 5],
            ['class' => 1, 'number' => '102', 'name' => 'Capital par dotation', 'parent' => '10', 'level' => 2, 'balance' => 'credit', 'sort' => 6],
            ['class' => 1, 'number' => '103', 'name' => 'Capital personnel', 'parent' => '10', 'level' => 2, 'balance' => 'credit', 'sort' => 7],
            ['class' => 1, 'number' => '104', 'name' => "Compte de l'exploitant", 'parent' => '10', 'level' => 2, 'balance' => 'credit', 'sort' => 8],
            ['class' => 1, 'number' => '11', 'name' => 'Réserves', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 10],
            ['class' => 1, 'number' => '111', 'name' => 'Réserve légale', 'parent' => '11', 'level' => 2, 'balance' => 'credit', 'sort' => 11],
            ['class' => 1, 'number' => '112', 'name' => 'Réserves statutaires ou contractuelles', 'parent' => '11', 'level' => 2, 'balance' => 'credit', 'sort' => 12],
            ['class' => 1, 'number' => '113', 'name' => 'Réserves réglementées', 'parent' => '11', 'level' => 2, 'balance' => 'credit', 'sort' => 13],
            ['class' => 1, 'number' => '118', 'name' => 'Autres réserves', 'parent' => '11', 'level' => 2, 'balance' => 'credit', 'sort' => 14],
            ['class' => 1, 'number' => '12', 'name' => 'Report à nouveau', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 20],
            ['class' => 1, 'number' => '121', 'name' => 'Report à nouveau créditeur', 'parent' => '12', 'level' => 2, 'balance' => 'credit', 'sort' => 21],
            ['class' => 1, 'number' => '129', 'name' => 'Report à nouveau débiteur', 'parent' => '12', 'level' => 2, 'balance' => 'debit', 'sort' => 22],
            ['class' => 1, 'number' => '13', 'name' => "Résultat net de l'exercice", 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 30],
            ['class' => 1, 'number' => '130', 'name' => "Résultat en instance d'affectation", 'parent' => '13', 'level' => 2, 'balance' => 'credit', 'sort' => 31],
            ['class' => 1, 'number' => '131', 'name' => 'Résultat net : bénéfice', 'parent' => '13', 'level' => 2, 'balance' => 'credit', 'sort' => 32],
            ['class' => 1, 'number' => '139', 'name' => 'Résultat net : perte', 'parent' => '13', 'level' => 2, 'balance' => 'debit', 'sort' => 33],
            ['class' => 1, 'number' => '14', 'name' => 'Subventions d\'investissement', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 40],
            ['class' => 1, 'number' => '141', 'name' => 'Subventions d\'équipement', 'parent' => '14', 'level' => 2, 'balance' => 'credit', 'sort' => 41],
            ['class' => 1, 'number' => '15', 'name' => 'Provisions réglementées et fonds assimilés', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 50],
            ['class' => 1, 'number' => '16', 'name' => 'Emprunts et dettes assimilées', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 60],
            ['class' => 1, 'number' => '161', 'name' => 'Emprunts obligataires', 'parent' => '16', 'level' => 2, 'balance' => 'credit', 'sort' => 61],
            ['class' => 1, 'number' => '162', 'name' => 'Emprunts et dettes auprès des établissements de crédit', 'parent' => '16', 'level' => 2, 'balance' => 'credit', 'sort' => 62],
            ['class' => 1, 'number' => '17', 'name' => 'Dettes de crédit-bail et contrats assimilés', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 70],
            ['class' => 1, 'number' => '18', 'name' => 'Dettes liées à des participations et comptes de liaison', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 80],
            ['class' => 1, 'number' => '19', 'name' => 'Provisions financières pour risques et charges', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 90],
            ['class' => 1, 'number' => '191', 'name' => 'Provisions pour risques', 'parent' => '19', 'level' => 2, 'balance' => 'credit', 'sort' => 91],
            ['class' => 1, 'number' => '194', 'name' => 'Provisions pour charges', 'parent' => '19', 'level' => 2, 'balance' => 'credit', 'sort' => 92],

            // Classe 2 - Comptes d'actif immobilisé
            ['class' => 2, 'number' => '20', 'name' => 'Charges immobilisées', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 2, 'number' => '201', 'name' => "Frais d'établissement", 'parent' => '20', 'level' => 2, 'balance' => 'debit', 'sort' => 2],
            ['class' => 2, 'number' => '202', 'name' => 'Charges à répartir sur plusieurs exercices', 'parent' => '20', 'level' => 2, 'balance' => 'debit', 'sort' => 3],
            ['class' => 2, 'number' => '206', 'name' => 'Primes de remboursement des obligations', 'parent' => '20', 'level' => 2, 'balance' => 'debit', 'sort' => 4],
            ['class' => 2, 'number' => '21', 'name' => 'Immobilisations incorporelles', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 2, 'number' => '211', 'name' => 'Frais de recherche et de développement', 'parent' => '21', 'level' => 2, 'balance' => 'debit', 'sort' => 11],
            ['class' => 2, 'number' => '212', 'name' => 'Brevets, licences, concessions et droits similaires', 'parent' => '21', 'level' => 2, 'balance' => 'debit', 'sort' => 12],
            ['class' => 2, 'number' => '213', 'name' => 'Logiciels', 'parent' => '21', 'level' => 2, 'balance' => 'debit', 'sort' => 13],
            ['class' => 2, 'number' => '214', 'name' => 'Fonds commercial', 'parent' => '21', 'level' => 2, 'balance' => 'debit', 'sort' => 14],
            ['class' => 2, 'number' => '22', 'name' => 'Terrains', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 20],
            ['class' => 2, 'number' => '221', 'name' => 'Terrains agricoles et forestiers', 'parent' => '22', 'level' => 2, 'balance' => 'debit', 'sort' => 21],
            ['class' => 2, 'number' => '222', 'name' => 'Terrains nus', 'parent' => '22', 'level' => 2, 'balance' => 'debit', 'sort' => 22],
            ['class' => 2, 'number' => '223', 'name' => 'Terrains bâtis', 'parent' => '22', 'level' => 2, 'balance' => 'debit', 'sort' => 23],
            ['class' => 2, 'number' => '23', 'name' => 'Bâtiments, installations techniques et agencements', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 30],
            ['class' => 2, 'number' => '231', 'name' => 'Bâtiments industriels', 'parent' => '23', 'level' => 2, 'balance' => 'debit', 'sort' => 31],
            ['class' => 2, 'number' => '232', 'name' => 'Bâtiments administratifs et commerciaux', 'parent' => '23', 'level' => 2, 'balance' => 'debit', 'sort' => 32],
            ['class' => 2, 'number' => '24', 'name' => 'Matériel', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 40],
            ['class' => 2, 'number' => '241', 'name' => 'Matériel et outillage industriel et commercial', 'parent' => '24', 'level' => 2, 'balance' => 'debit', 'sort' => 41],
            ['class' => 2, 'number' => '244', 'name' => 'Matériel et mobilier de bureau', 'parent' => '24', 'level' => 2, 'balance' => 'debit', 'sort' => 42],
            ['class' => 2, 'number' => '245', 'name' => 'Matériel de transport', 'parent' => '24', 'level' => 2, 'balance' => 'debit', 'sort' => 43],
            ['class' => 2, 'number' => '25', 'name' => 'Avances et acomptes versés sur immobilisations', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 50],
            ['class' => 2, 'number' => '26', 'name' => 'Titres de participation', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 60],
            ['class' => 2, 'number' => '27', 'name' => 'Autres immobilisations financières', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 70],
            ['class' => 2, 'number' => '28', 'name' => 'Amortissements', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 80],
            ['class' => 2, 'number' => '29', 'name' => 'Provisions pour dépréciation', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 90],

            // Classe 3 - Comptes de stocks
            ['class' => 3, 'number' => '31', 'name' => 'Marchandises', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 3, 'number' => '32', 'name' => 'Matières premières et fournitures liées', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 2],
            ['class' => 3, 'number' => '33', 'name' => 'Autres approvisionnements', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 3],
            ['class' => 3, 'number' => '34', 'name' => 'Produits en cours', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 4],
            ['class' => 3, 'number' => '35', 'name' => 'Services en cours', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 5],
            ['class' => 3, 'number' => '36', 'name' => 'Produits finis', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 6],
            ['class' => 3, 'number' => '37', 'name' => 'Produits intermédiaires et résiduels', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 7],
            ['class' => 3, 'number' => '38', 'name' => 'Stocks en cours de route, en consignation ou en dépôt', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 8],
            ['class' => 3, 'number' => '39', 'name' => 'Dépréciations des stocks', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 9],

            // Classe 4 - Comptes de tiers
            ['class' => 4, 'number' => '40', 'name' => 'Fournisseurs et comptes rattachés', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 4, 'number' => '401', 'name' => 'Fournisseurs, dettes en compte', 'parent' => '40', 'level' => 2, 'balance' => 'credit', 'sort' => 2],
            ['class' => 4, 'number' => '402', 'name' => 'Fournisseurs, effets à payer', 'parent' => '40', 'level' => 2, 'balance' => 'credit', 'sort' => 3],
            ['class' => 4, 'number' => '41', 'name' => 'Clients et comptes rattachés', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 4, 'number' => '411', 'name' => 'Clients', 'parent' => '41', 'level' => 2, 'balance' => 'debit', 'sort' => 11],
            ['class' => 4, 'number' => '412', 'name' => 'Clients, effets à recevoir', 'parent' => '41', 'level' => 2, 'balance' => 'debit', 'sort' => 12],
            ['class' => 4, 'number' => '42', 'name' => 'Personnel', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 20],
            ['class' => 4, 'number' => '421', 'name' => 'Personnel, avances et acomptes', 'parent' => '42', 'level' => 2, 'balance' => 'debit', 'sort' => 21],
            ['class' => 4, 'number' => '422', 'name' => 'Personnel, rémunérations dues', 'parent' => '42', 'level' => 2, 'balance' => 'credit', 'sort' => 22],
            ['class' => 4, 'number' => '43', 'name' => 'Organismes sociaux', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 30],
            ['class' => 4, 'number' => '44', 'name' => 'État et collectivités publiques', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 40],
            ['class' => 4, 'number' => '441', 'name' => 'État, impôts sur les bénéfices', 'parent' => '44', 'level' => 2, 'balance' => 'credit', 'sort' => 41],
            ['class' => 4, 'number' => '443', 'name' => 'État, TVA facturée', 'parent' => '44', 'level' => 2, 'balance' => 'credit', 'sort' => 42],
            ['class' => 4, 'number' => '445', 'name' => 'État, TVA récupérable', 'parent' => '44', 'level' => 2, 'balance' => 'debit', 'sort' => 43],
            ['class' => 4, 'number' => '45', 'name' => 'Organismes internationaux', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 50],
            ['class' => 4, 'number' => '46', 'name' => 'Associés et groupe', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 60],
            ['class' => 4, 'number' => '47', 'name' => 'Débiteurs et créditeurs divers', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 70],
            ['class' => 4, 'number' => '48', 'name' => 'Créances et dettes hors activités ordinaires', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 80],
            ['class' => 4, 'number' => '49', 'name' => 'Dépréciations et provisions pour risques à court terme', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 90],

            // Classe 5 - Comptes de trésorerie
            ['class' => 5, 'number' => '50', 'name' => 'Titres de placement', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 5, 'number' => '51', 'name' => 'Valeurs à encaisser', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 5, 'number' => '52', 'name' => 'Banques', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 20],
            ['class' => 5, 'number' => '521', 'name' => 'Banques locales', 'parent' => '52', 'level' => 2, 'balance' => 'debit', 'sort' => 21],
            ['class' => 5, 'number' => '522', 'name' => 'Banques autres États UEMOA', 'parent' => '52', 'level' => 2, 'balance' => 'debit', 'sort' => 22],
            ['class' => 5, 'number' => '53', 'name' => 'Établissements financiers et assimilés', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 30],
            ['class' => 5, 'number' => '54', 'name' => 'Instruments de trésorerie', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 40],
            ['class' => 5, 'number' => '56', 'name' => 'Banques, crédits de trésorerie et d\'escompte', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 60],
            ['class' => 5, 'number' => '57', 'name' => 'Caisse', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 70],
            ['class' => 5, 'number' => '571', 'name' => 'Caisse siège social', 'parent' => '57', 'level' => 2, 'balance' => 'debit', 'sort' => 71],
            ['class' => 5, 'number' => '58', 'name' => 'Régies d\'avances, accréditifs et virements internes', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 80],
            ['class' => 5, 'number' => '59', 'name' => 'Dépréciations et provisions pour risques à court terme', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 90],

            // Classe 6 - Comptes de charges
            ['class' => 6, 'number' => '60', 'name' => 'Achats et variations de stocks', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 6, 'number' => '601', 'name' => 'Achats de marchandises', 'parent' => '60', 'level' => 2, 'balance' => 'debit', 'sort' => 2],
            ['class' => 6, 'number' => '602', 'name' => 'Achats de matières premières', 'parent' => '60', 'level' => 2, 'balance' => 'debit', 'sort' => 3],
            ['class' => 6, 'number' => '604', 'name' => 'Achats stockés de matières et fournitures consommables', 'parent' => '60', 'level' => 2, 'balance' => 'debit', 'sort' => 4],
            ['class' => 6, 'number' => '605', 'name' => 'Autres achats', 'parent' => '60', 'level' => 2, 'balance' => 'debit', 'sort' => 5],
            ['class' => 6, 'number' => '61', 'name' => 'Transports', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 10],
            ['class' => 6, 'number' => '62', 'name' => 'Services extérieurs A', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 20],
            ['class' => 6, 'number' => '63', 'name' => 'Services extérieurs B', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 30],
            ['class' => 6, 'number' => '64', 'name' => 'Impôts et taxes', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 40],
            ['class' => 6, 'number' => '65', 'name' => 'Autres charges', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 50],
            ['class' => 6, 'number' => '66', 'name' => 'Charges de personnel', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 60],
            ['class' => 6, 'number' => '661', 'name' => 'Rémunérations directes versées au personnel national', 'parent' => '66', 'level' => 2, 'balance' => 'debit', 'sort' => 61],
            ['class' => 6, 'number' => '664', 'name' => 'Charges sociales', 'parent' => '66', 'level' => 2, 'balance' => 'debit', 'sort' => 62],
            ['class' => 6, 'number' => '67', 'name' => 'Frais financiers et charges assimilées', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 70],
            ['class' => 6, 'number' => '68', 'name' => 'Dotations aux amortissements', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 80],
            ['class' => 6, 'number' => '69', 'name' => 'Dotations aux provisions', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 90],

            // Classe 7 - Comptes de produits
            ['class' => 7, 'number' => '70', 'name' => 'Ventes', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 1],
            ['class' => 7, 'number' => '701', 'name' => 'Ventes de marchandises', 'parent' => '70', 'level' => 2, 'balance' => 'credit', 'sort' => 2],
            ['class' => 7, 'number' => '702', 'name' => 'Ventes de produits finis', 'parent' => '70', 'level' => 2, 'balance' => 'credit', 'sort' => 3],
            ['class' => 7, 'number' => '706', 'name' => 'Services vendus', 'parent' => '70', 'level' => 2, 'balance' => 'credit', 'sort' => 4],
            ['class' => 7, 'number' => '71', 'name' => 'Subventions d\'exploitation', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 10],
            ['class' => 7, 'number' => '72', 'name' => 'Production immobilisée', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 20],
            ['class' => 7, 'number' => '73', 'name' => 'Variations de stocks de produits et en-cours', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 30],
            ['class' => 7, 'number' => '75', 'name' => 'Autres produits', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 50],
            ['class' => 7, 'number' => '77', 'name' => 'Revenus financiers et produits assimilés', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 70],
            ['class' => 7, 'number' => '78', 'name' => 'Transferts de charges', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 80],
            ['class' => 7, 'number' => '79', 'name' => 'Reprises de provisions', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 90],

            // Classe 8 - Autres charges et produits
            ['class' => 8, 'number' => '81', 'name' => 'Valeurs comptables des cessions d\'immobilisations', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 8, 'number' => '82', 'name' => 'Produits des cessions d\'immobilisations', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 2],
            ['class' => 8, 'number' => '83', 'name' => 'Charges hors activités ordinaires', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 3],
            ['class' => 8, 'number' => '84', 'name' => 'Produits hors activités ordinaires', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 4],
            ['class' => 8, 'number' => '85', 'name' => 'Dotations hors activités ordinaires', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 5],
            ['class' => 8, 'number' => '86', 'name' => 'Reprises hors activités ordinaires', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 6],
            ['class' => 8, 'number' => '87', 'name' => 'Participation des travailleurs', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 7],
            ['class' => 8, 'number' => '88', 'name' => 'Subventions d\'équilibre', 'parent' => null, 'level' => 1, 'balance' => 'credit', 'sort' => 8],
            ['class' => 8, 'number' => '89', 'name' => 'Impôts sur le résultat', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 9],

            // Classe 9 - Engagements hors bilan
            ['class' => 9, 'number' => '90', 'name' => 'Engagements obtenus et engagements accordés', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 1],
            ['class' => 9, 'number' => '91', 'name' => 'Engagements obtenus sur opérations de crédit-bail', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 2],
            ['class' => 9, 'number' => '92', 'name' => 'Reclassement par nature des charges des activités', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 3],
            ['class' => 9, 'number' => '93', 'name' => 'Comptes de coûts des sections analytiques', 'parent' => null, 'level' => 1, 'balance' => 'debit', 'sort' => 4],
        ];
    }

    private function seedAccountingSystems(): void
    {
        $systems = [
            [
                'name' => 'Système Normal',
                'code' => 'SN',
                'description' => "Le Système Normal est le système de référence du SYSCOHADA. Il s'applique aux grandes entreprises et aux entreprises dont le chiffre d'affaires annuel dépasse 100 millions de FCFA. Il exige la tenue d'une comptabilité complète avec tous les états financiers.",
                'applicable_entities' => ['Grandes entreprises', 'Sociétés anonymes', 'Établissements publics'],
                'required_statements' => ['Bilan', 'Compte de résultat', 'TAFIRE', 'État annexé'],
                'revenue_threshold' => '> 100 000 000 FCFA',
                'sort_order' => 1,
            ],
            [
                'name' => 'Système Allégé',
                'code' => 'SA',
                'description' => "Le Système Allégé est destiné aux entreprises de taille moyenne dont le chiffre d'affaires se situe entre 30 et 100 millions de FCFA. Les états financiers sont simplifiés mais restent conformes aux principes OHADA.",
                'applicable_entities' => ['PME', 'SARL de taille moyenne', 'Entreprises individuelles moyennes'],
                'required_statements' => ['Bilan simplifié', 'Compte de résultat simplifié', 'État annexé simplifié'],
                'revenue_threshold' => '30 000 000 - 100 000 000 FCFA',
                'sort_order' => 2,
            ],
            [
                'name' => 'Système Minimal de Trésorerie',
                'code' => 'SMT',
                'description' => "Le Système Minimal de Trésorerie (SMT) est destiné aux très petites entreprises dont le chiffre d'affaires annuel ne dépasse pas 30 millions de FCFA. La comptabilité est basée sur les encaissements et décaissements.",
                'applicable_entities' => ['Très petites entreprises', 'Micro-entreprises', 'Artisans', 'Petits commerçants'],
                'required_statements' => ['État des recettes et dépenses', 'État du patrimoine'],
                'revenue_threshold' => '< 30 000 000 FCFA',
                'sort_order' => 3,
            ],
        ];

        foreach ($systems as $system) {
            AccountingSystem::firstOrCreate(
                ['code' => $system['code']],
                $system
            );
        }
    }

    private function seedFinancialStatements(): void
    {
        $normalSystem = AccountingSystem::where('code', 'SN')->first();
        if (! $normalSystem) {
            return;
        }

        $statements = [
            [
                'name' => 'Bilan',
                'code' => 'BILAN',
                'description' => "Le Bilan est un état financier qui présente la situation patrimoniale de l'entreprise à une date donnée. Il comprend l'Actif (emplois) et le Passif (ressources). L'Actif regroupe les immobilisations, stocks, créances et trésorerie. Le Passif comprend les capitaux propres, dettes financières et dettes d'exploitation.",
                'accounting_system_id' => $normalSystem->id,
                'is_required' => true,
                'sort_order' => 1,
                'structure' => [
                    'actif' => ['Actif immobilisé', 'Actif circulant', 'Trésorerie-Actif'],
                    'passif' => ['Capitaux propres et ressources assimilées', 'Dettes financières et ressources assimilées', 'Passif circulant', 'Trésorerie-Passif'],
                ],
            ],
            [
                'name' => 'Compte de Résultat',
                'code' => 'CR',
                'description' => "Le Compte de Résultat récapitule les produits et charges de l'exercice, sans qu'il soit tenu compte de leur date d'encaissement ou de paiement. Il fait apparaître le résultat net de l'exercice (bénéfice ou perte).",
                'accounting_system_id' => $normalSystem->id,
                'is_required' => true,
                'sort_order' => 2,
                'structure' => [
                    'sections' => [
                        'Activité d\'exploitation',
                        'Activité financière',
                        'Hors activités ordinaires',
                        'Participation et impôts',
                        'Résultat net',
                    ],
                ],
            ],
            [
                'name' => 'Tableau Financier des Ressources et Emplois (TAFIRE)',
                'code' => 'TAFIRE',
                'description' => "Le TAFIRE est un état financier propre au SYSCOHADA qui retrace les flux de ressources et d'emplois de l'exercice. Il permet d'analyser les variations du patrimoine et d'expliquer l'évolution de la situation financière.",
                'accounting_system_id' => $normalSystem->id,
                'is_required' => true,
                'sort_order' => 3,
                'structure' => [
                    'parties' => [
                        '1ère partie : Détermination des soldes financiers',
                        '2ème partie : Tableau des emplois et ressources',
                    ],
                ],
            ],
            [
                'name' => 'État Annexé',
                'code' => 'EA',
                'description' => "L'État Annexé complète et commente l'information donnée par les autres états financiers. Il fournit des explications nécessaires à une meilleure compréhension du Bilan et du Compte de Résultat, notamment les méthodes comptables utilisées et les événements significatifs.",
                'accounting_system_id' => $normalSystem->id,
                'is_required' => true,
                'sort_order' => 4,
                'structure' => [
                    'notes' => [
                        'Règles et méthodes comptables',
                        'Compléments d\'information relatifs au Bilan',
                        'Compléments d\'information relatifs au Compte de Résultat',
                        'Autres informations',
                    ],
                ],
            ],
        ];

        foreach ($statements as $statement) {
            OhadaFinancialStatement::firstOrCreate(
                ['code' => $statement['code']],
                $statement
            );
        }
    }
}
