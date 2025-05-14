<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use App\Services\OfferService;
use App\Http\Requests\Offer\StoreOfferRequest;
use App\Http\Requests\Offer\UpdateOfferRequest;

class OfferController extends Controller
{

    /**
     * The service class responsible for handling offer-related business logic.
     *
     * @var \App\Services\OfferService
     */
    protected $offerService;

    /**
     * Create a new OrderController instance and inject the offerService.
     *
     * @param \App\Services\OfferService $offerService The service responsible for order operations.
     */
    public function __construct(OfferService $offerService)
    {
        $this->offerService = $offerService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return $this->success(
            Offer::latest()->get(),
        'Offers retrieved successfully',200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfferRequest $request)
    {
        return $this->success(
            $this->offerService->storeOffer($request->validated()),
            'Offers created successfully.',
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Offer $offer)
    {
        return $this->success(
             $offer->load(['products']),
        'Offers retrieved successfully',200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfferRequest $request, Offer $offer)
    {
        return $this->success(
            $this->offerService->updateOffer($offer,$request->validated()),
            'Offers updated successfully.',
            201
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Offer $offer)
    {
        $offer->delete();
          return $this->success(
            null,
        'Offers deleted successfully',200);
    }
}
