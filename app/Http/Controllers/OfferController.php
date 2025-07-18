<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use Illuminate\Http\Request;
use App\Services\OfferService;
use Illuminate\Support\Facades\Auth;
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
        $user = Auth::user();
        // Check if user has role super admin or admin
        if ($user->hasRole('super admin') || $user->hasRole('admin')) {
            $offers = Offer::all();
        } else {
            // Default: show only offers from store managed by this user
            $offers = Offer::whereHas('store', function ($q) use ($user) {
                $q->where('manager_id', $user->id);
            })->get();
        }
        return $this->success($offers, 'Offers retrieved successfully', 200);
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
        $offer->load([
            'products.images',
        ]);

        // Calculate discounted price for each product
        $offer->products->transform(function ($product) use ($offer) {
            $price = $product->price;
            $discount = $offer->discount_percentage;

            $finalPrice = round($price - ($price * $discount / 100), 2);

            $product->final_price = $finalPrice;

            return $product;
        });

        return $this->success($offer, 'Offer retrieved successfully', 200);
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

    /**
     * get offer's form data
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getOfferFormData(Request $request){

        
        $offerId = $request->get('offer');
        $result = $this->offerService->getFormData($offerId);

        if (!$result['status']) {
            return response()->json(['message' => $result['message']], $result['code']);
        }
        return $this->success(
            $result['data'],
        'data retrieved successfully',200);
    }

    /**
     * return all offers for customers 
     * @return \Illuminate\Http\JsonResponse
     */
    public function allOffers(){
        return $this->success(
            Offer::latest()->available()->get(),
            'All offers retrieved successfully',
            200
        );
    }

    /**
     * toggle Available
     * @param \App\Models\Offer $offer
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleAvailable(Offer $offer)
    {
        return $this->success(
            $offer->toggleAvailability(),
        'Offers has been successfully Toggled',200);
    }



}
