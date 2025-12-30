<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentCollectionRequest;
use App\Http\Resources\PaymentCollectionResource;
use App\Services\PaymentCollectionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentCollectionController extends Controller
{
    public function __construct(
        protected PaymentCollectionService $service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $perPage = $request->integer('per_page', 15);

        $collections = $this->service->index($filters, $perPage);

        return Inertia::render('Admin/PaymentCollections/Index', [
            'collections' => PaymentCollectionResource::collection($collections)->additional([
                'meta' => [
                    'current_page' => $collections->currentPage(),
                    'from' => $collections->firstItem(),
                    'last_page' => $collections->lastPage(),
                    'per_page' => $collections->perPage(),
                    'to' => $collections->lastItem(),
                    'total' => $collections->total(),
                ],
                'links' => [
                    'first' => $collections->url(1),
                    'last' => $collections->url($collections->lastPage()),
                    'prev' => $collections->previousPageUrl(),
                    'next' => $collections->nextPageUrl(),
                ],
            ]),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/PaymentCollections/Form');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PaymentCollectionRequest $request)
    {
        $collection = $this->service->create(
            $request->validated(),
            $request->user()->id
        );

        return redirect()->route('admin.payment-collections.index')
            ->with('success', 'Payment collection created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): Response
    {
        $collection = $this->service->show($id);

        return Inertia::render('Admin/PaymentCollections/Show', [
            'collection' => (new PaymentCollectionResource($collection))->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(int $id): Response
    {
        $collection = $this->service->show($id);

        return Inertia::render('Admin/PaymentCollections/Form', [
            'collection' => (new PaymentCollectionResource($collection))->resolve(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PaymentCollectionRequest $request, int $id)
    {
        $collection = $this->service->update($id, $request->validated());

        return redirect()->route('admin.payment-collections.index')
            ->with('success', 'Payment collection updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $this->service->delete($id);

        return redirect()->route('admin.payment-collections.index')
            ->with('success', 'Payment collection deleted successfully.');
    }
}
