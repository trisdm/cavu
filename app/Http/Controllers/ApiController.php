<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\BookingCounter;
use App\Models\Booking;
class ApiController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function checkRange(Request $request) : JsonResponse
    {
        $from  = Booking::formatDate($request->input('from'));
        $to    = Booking::formatDate($request->input('to'));

        $checkFull = BookingCounter::checkFull($from, $to);
        $validDates=Booking::validateDates($from, $to);


        $response = [
            'name'  => 'check-range',
            'days'  => BookingCounter::checkRangeArray($from, $to)
        ];

        if(!$checkFull && $validDates) {
            $response['available_to_book']    = true;
            $response['booking_cost'] = Booking::calculatePrice($from, $to);
        } else {
            $response['available_to_book']    = false;
        }
        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function amendBooking(Request $request) : JsonResponse
    {
        $newFrom = Booking::formatDate($request->input('from'));
        $newTo    = Booking::formatDate($request->input('to'));
        $extBookingId  = $request->input('booking_id');

        if (!Booking::validateDates($newFrom, $newTo)) {
            $resp = [
                'name' => 'amend-booking',
                'status'=> 'error',
                'reason'=> 'invalid dates'
            ];
        }  else {
           $resp = Booking::amend($newFrom, $newTo, $extBookingId);
        }
        return response()->json($resp);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelBooking(Request $request) : JsonResponse
    {
        $extBookingId  = $request->input('booking_id');
        $resp = BookingCounter::where('external_booking_id',  $extBookingId)
                ->update(['status' => 'canceled']);
        if($resp) {
            return response()->json([
                'name' => 'cancel-booking',
                'status' => 'success',
                'reason' => 'Booking id: '. $extBookingId . " was cancelled"
            ]);
        } else {
            return response()->json([
                'name' => 'cancel-booking',
                'status' => 'error',
                'reason' => 'Booking id: '. $extBookingId . " was not cancelled"
            ]);
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     *
     */
    public function book(Request $request) : JsonResponse
    {
        $carRegistration  = $request->input('car_registration');
        $email  = $request->input('email');
        $from = Booking::formatDate($request->input('from'));
        $to     = Booking::formatDate($request->input('to'));

        $extBookingId = Booking::createExtId();
        $booking = [
            'external_id' => $extBookingId,
            'car_registration' => $carRegistration,
            'email' => $email,
        ];

        $validDates = Booking::validateDates($from, $to);
        $checkFull = BookingCounter::checkFull($from, $to);

        if ($validDates && !$checkFull && Booking::insert($booking))
        {
            BookingCounter::insertCounter($from, $to, $extBookingId);
            return response()->json([
                'name' => 'book',
                'status' => 'success',
                'booking_id' => $extBookingId
            ]);
        } else {
            if(!$validDates){
                $reason = "Invalid booking dates";
            } else if ($checkFull){
                $reason = "Dates are unavailable";
            } else {
                $reason = "Failed to process booking";
            }

            return response()->json([
                'name' => 'book',
                'status' => 'error',
                'reason' => $reason
            ]);
        }
    }
}
