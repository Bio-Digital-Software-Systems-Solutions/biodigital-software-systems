<?php

use App\Models\Accounting\AccountingSystem;
use App\Models\Accounting\OhadaAccount;
use App\Models\Accounting\OhadaAccountClass;
use App\Models\Accounting\OhadaFinancialStatement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to an account class', function (): void {
    $class = OhadaAccountClass::factory()->create();
    $account = OhadaAccount::factory()->create(['class_id' => $class->id]);

    expect($account->accountClass)->toBeInstanceOf(OhadaAccountClass::class);
    expect($account->accountClass->id)->toBe($class->id);
});

it('can have a parent account', function (): void {
    $class = OhadaAccountClass::factory()->create();
    $parent = OhadaAccount::factory()->create(['class_id' => $class->id]);
    $child = OhadaAccount::factory()->create([
        'class_id' => $class->id,
        'parent_id' => $parent->id,
    ]);

    expect($child->parent)->toBeInstanceOf(OhadaAccount::class);
    expect($child->parent->id)->toBe($parent->id);
});

it('can have children accounts', function (): void {
    $class = OhadaAccountClass::factory()->create();
    $parent = OhadaAccount::factory()->create(['class_id' => $class->id]);
    OhadaAccount::factory()->count(3)->create([
        'class_id' => $class->id,
        'parent_id' => $parent->id,
    ]);

    expect($parent->children)->toHaveCount(3);
});

it('scopes to root accounts', function (): void {
    $class = OhadaAccountClass::factory()->create();
    $root = OhadaAccount::factory()->create(['class_id' => $class->id, 'parent_id' => null]);
    OhadaAccount::factory()->create(['class_id' => $class->id, 'parent_id' => $root->id]);

    $roots = OhadaAccount::roots()->get();

    expect($roots)->toHaveCount(1);
    expect($roots->first()->id)->toBe($root->id);
});

it('scopes by class number', function (): void {
    $class1 = OhadaAccountClass::factory()->create(['class_number' => 1]);
    $class2 = OhadaAccountClass::factory()->create(['class_number' => 2]);
    OhadaAccount::factory()->count(2)->create(['class_id' => $class1->id]);
    OhadaAccount::factory()->create(['class_id' => $class2->id]);

    $accounts = OhadaAccount::byClass(1)->get();

    expect($accounts)->toHaveCount(2);
});

it('account class has many accounts', function (): void {
    $class = OhadaAccountClass::factory()->create();
    OhadaAccount::factory()->count(5)->create(['class_id' => $class->id]);

    expect($class->accounts)->toHaveCount(5);
});

it('accounting system has many financial statements', function (): void {
    $system = AccountingSystem::factory()->create();
    OhadaFinancialStatement::factory()->count(4)->create(['accounting_system_id' => $system->id]);

    expect($system->financialStatements)->toHaveCount(4);
});

it('financial statement belongs to accounting system', function (): void {
    $system = AccountingSystem::factory()->create();
    $statement = OhadaFinancialStatement::factory()->create(['accounting_system_id' => $system->id]);

    expect($statement->accountingSystem)->toBeInstanceOf(AccountingSystem::class);
    expect($statement->accountingSystem->id)->toBe($system->id);
});
