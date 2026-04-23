<?php

namespace App\Http\Controllers\Agile;

use App\Enums\Agile\TestScenarioExecutionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Agile\RecordTestRunRequest;
use App\Http\Requests\Agile\StoreTestScenarioRequest;
use App\Http\Requests\Agile\UpdateTestScenarioRequest;
use App\Http\Resources\Agile\TestScenarioResource;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\TestScenario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TestScenarioController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    public function index(AcceptanceCriterion $acceptanceCriterion): Response
    {
        $this->authorize('viewAny', TestScenario::class);

        $scenarios = $acceptanceCriterion->testScenarios()->with('lastExecutedBy')->get();

        return Inertia::render('Agile/TestScenarios/Index', [
            'acceptance_criterion_id' => $acceptanceCriterion->id,
            'scenarios' => TestScenarioResource::collection($scenarios),
        ]);
    }

    public function show(TestScenario $testScenario): Response
    {
        $this->authorize('view', $testScenario);

        $testScenario->load(['acceptanceCriterion', 'lastExecutedBy']);

        return Inertia::render('Agile/TestScenarios/Show', [
            'scenario' => new TestScenarioResource($testScenario),
        ]);
    }

    public function store(StoreTestScenarioRequest $request, AcceptanceCriterion $acceptanceCriterion): RedirectResponse
    {
        $data = $request->validated();
        $data['acceptance_criterion_id'] = $acceptanceCriterion->id;

        $scenario = TestScenario::create($data);

        return redirect()->route('agile.acceptance-criteria.test-scenarios.index', $acceptanceCriterion);
    }

    public function update(UpdateTestScenarioRequest $request, TestScenario $testScenario): RedirectResponse
    {
        $testScenario->update($request->validated());

        return redirect()->route('agile.test-scenarios.show', $testScenario);
    }

    public function destroy(TestScenario $testScenario): RedirectResponse
    {
        $this->authorize('delete', $testScenario);
        $testScenario->delete();

        return redirect()->back();
    }

    public function recordRun(RecordTestRunRequest $request, TestScenario $scenario): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $scenario->forceFill([
            'execution_status' => TestScenarioExecutionStatus::from($request->string('status')->toString()),
            'last_executed_by' => $user->id,
            'last_executed_at' => now(),
            'failure_notes' => $request->input('failure_notes'),
        ])->save();

        return (new TestScenarioResource($scenario))
            ->response()
            ->setStatusCode(200);
    }
}
