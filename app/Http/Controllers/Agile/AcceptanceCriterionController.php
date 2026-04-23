<?php

namespace App\Http\Controllers\Agile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agile\RejectAcceptanceCriterionRequest;
use App\Http\Requests\Agile\ReorderAcceptanceCriteriaRequest;
use App\Http\Requests\Agile\StoreAcceptanceCriterionRequest;
use App\Http\Requests\Agile\UpdateAcceptanceCriterionRequest;
use App\Http\Requests\Agile\ValidateAcceptanceCriterionRequest;
use App\Http\Resources\Agile\AcceptanceCriterionResource;
use App\Models\Agile\AcceptanceCriterion;
use App\Models\Agile\UserStory;
use App\Services\Agile\AcceptanceCriterionValidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AcceptanceCriterionController extends Controller
{
    public function __construct(
        private readonly AcceptanceCriterionValidationService $service,
    ) {
        $this->middleware(['auth', 'verified']);
    }

    public function index(UserStory $userStory): Response
    {
        $this->authorize('viewAny', AcceptanceCriterion::class);

        $criteria = $userStory->acceptanceCriteria()->with('validatedBy')->withCount('testScenarios')->get();

        return Inertia::render('Agile/AcceptanceCriteria/Index', [
            'user_story_id' => $userStory->id,
            'criteria' => AcceptanceCriterionResource::collection($criteria),
        ]);
    }

    public function show(AcceptanceCriterion $acceptanceCriterion): Response
    {
        $this->authorize('view', $acceptanceCriterion);

        $acceptanceCriterion->load(['validatedBy', 'testScenarios']);

        return Inertia::render('Agile/AcceptanceCriteria/Show', [
            'criterion' => new AcceptanceCriterionResource($acceptanceCriterion),
        ]);
    }

    public function store(StoreAcceptanceCriterionRequest $request, UserStory $userStory): RedirectResponse
    {
        $data = $request->validated();
        $data['user_story_id'] = $userStory->id;

        // Auto-position si non fourni : dernière position + 1
        if (! isset($data['position'])) {
            $data['position'] = (int) ($userStory->acceptanceCriteria()->max('position') ?? 0) + 1;
        }

        $ac = AcceptanceCriterion::create($data);

        return redirect()->route('agile.user-stories.acceptance-criteria.index', $userStory);
    }

    public function update(UpdateAcceptanceCriterionRequest $request, AcceptanceCriterion $acceptanceCriterion): RedirectResponse
    {
        $acceptanceCriterion->update($request->validated());

        return redirect()->route('agile.acceptance-criteria.show', $acceptanceCriterion);
    }

    public function destroy(AcceptanceCriterion $acceptanceCriterion): RedirectResponse
    {
        $this->authorize('delete', $acceptanceCriterion);
        $this->service->guardDelete($acceptanceCriterion);

        $acceptanceCriterion->delete();

        return redirect()->back();
    }

    public function validateCriterion(ValidateAcceptanceCriterionRequest $request, AcceptanceCriterion $criterion): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $updated = $this->service->validate($criterion, $user, $request->input('notes'));

        return (new AcceptanceCriterionResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    public function reject(RejectAcceptanceCriterionRequest $request, AcceptanceCriterion $criterion): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $updated = $this->service->reject($criterion, $user, $request->input('notes'));

        return (new AcceptanceCriterionResource($updated))
            ->response()
            ->setStatusCode(200);
    }

    public function reorder(ReorderAcceptanceCriteriaRequest $request, UserStory $userStory): JsonResponse
    {
        $this->service->reorder($userStory, $request->input('ordered_ids', []));

        return response()->json(['ok' => true]);
    }
}
