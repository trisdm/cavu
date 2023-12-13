<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $fillable = ['external_id'];
    use HasFactory;

    /**
     * @param int $newFrom
     * @param int $newTo
     * @param string $extBookingId
     * @return array|string[]
     *
     * This function is used to amend an existing booking and return an array
     * that can be placed inside a request and returned via json.
     *
     * It amends the dates and calculates any changes to the price that would be paid
     */
    public static function amend(int $newFrom, int $newTo, string $extBookingId) : array
    {
        $resp = [];
        $booking = Booking::where('external_id', $extBookingId)
            ->get(['external_id'])
            ->first();

        if ($booking) {
            $externalId = $booking['external_id'];
            $oldFirst = BookingCounter::where('external_booking_id', $externalId)
                ->get()->first();
            $oldLast = BookingCounter::where('external_booking_id', $externalId)
                ->get()->last();


            $previousFrom = Booking::formatDate($oldFirst['booking_day']);
            $previousTo = Booking::formatDate($oldLast['booking_day']);

            if($newFrom == $previousFrom && $newTo == $previousTo) {
                //do nothing
                $resp = [
                    'name' => 'amend-booking',
                    'status'=> 'error',
                    'reason'=> 'dates are unchanged'
                ];
            }


            $prevDiff = $previousTo - $previousFrom;
            $newDiff = $newTo - $newFrom;

            $decreasing = $newDiff <= $prevDiff;

            $full = true;

            if($newTo < $previousFrom || $newFrom > $previousTo) {

                //check all days as we are booking a completely new range of days
                $full = BookingCounter::checkFull($newFrom, $newTo);

                if(!$full) {
                    BookingCounter::updateCounter($previousFrom, $previousTo, $externalId, 'amended');
                    BookingCounter::insertCounter($newFrom, $newTo, $externalId);
                }
            } elseif ($decreasing) {


                //decreasing amendments
                if ($newFrom >= $previousFrom && $newTo <= $previousTo) {
                    $full = false;
                    //decrease bookings within bounds no checks necessary

                    BookingCounter::updateCounter($previousFrom, $previousTo, $externalId, 'amended');
                    BookingCounter::insertCounter($newFrom, $newTo, $externalId);
                }

                if ($newFrom < $previousFrom) {
                    //we are partially out of range with an earlier from date
                    //check dates from newfrom to previousfrom
                    $full = BookingCounter::checkFull($newFrom, $previousFrom);
                    if(!$full) {
                        BookingCounter::updateCounter($newTo, $previousTo, $externalId, 'amended');
                        BookingCounter::insertCounter($newFrom, $previousFrom, $externalId);
                    }
                }
                if ($newFrom > $previousFrom) {
                    //we are partially out of range with a later end date
                    // check dates from newTo to previousTo
                    $full = BookingCounter::checkFull($previousTo, $newTo);
                    if(!$full) {
                        BookingCounter::updateCounter($previousFrom, $newFrom, $externalId, 'amended');
                        BookingCounter::insertCounter($previousTo, $newTo, $externalId);
                    }
                }
                //end decreasing amendments
            } else {
                //increasing amendments
                if($newFrom < $previousFrom && $newTo == $previousTo) {
                    //check newFrom to previousFrom
                    $full = BookingCounter::checkFull($newFrom, $previousFrom);
                    if(!$full) {
                        BookingCounter::insertCounter($newFrom, $previousFrom, $externalId);
                    }
                }

                if($newFrom == $previousFrom && $newTo > $previousTo) {
                    //check previousTo to newTo
                    $full = BookingCounter::checkFull($previousTo, $newTo);
                    if(!$full) {
                        BookingCounter::insertCounter($previousTo, $newTo, $externalId);
                    }
                }
                if($newFrom < $previousFrom && $newTo > $previousTo) {

                    if(!BookingCounter::checkFull($previousTo, $newTo) && !BookingCounter::checkFull($newFrom, $previousFrom)){
                        $full = false;
                        BookingCounter::insertCounter($previousTo, $newTo, $externalId);
                        BookingCounter::insertCounter($newFrom, $previousFrom, $externalId);
                    }
                }

                if($newFrom > $previousFrom && $newTo > $previousTo) {
                    $full = BookingCounter::checkFull($previousTo, $newTo);
                    if(!$full) {
                        BookingCounter::updateCounter($previousFrom, $newFrom, $externalId, 'amended');
                        BookingCounter::insertCounter($previousTo, $newTo, $externalId);
                    }
                }

                if($newFrom < $previousFrom && $newTo < $previousTo) {
                    $full = BookingCounter::checkFull($previousTo, $newTo);
                    if(!$full) {
                        BookingCounter::updateCounter($newTo, $previousTo, $externalId, 'amended');
                        BookingCounter::insertCounter($newFrom, $previousFrom, $externalId);
                    }
                }

                if($newFrom > $previousFrom && $newTo > $previousTo) {
                    $full = BookingCounter::checkFull($previousTo, $newTo);
                    if(!$full) {
                        BookingCounter::updateCounter($previousFrom, $newFrom, $externalId, 'amended');
                        BookingCounter::insertCounter($previousTo, $newTo, $externalId);
                    }
                }
            }
            if(!$full) {
                $newCost = self::calculatePrice($newFrom, $newTo);
                $oldCost = self::calculatePrice($previousFrom, $previousTo);

                $costKey = $newCost < $oldCost ? "refund amount" : "additional cost";
                $costValue = abs($newCost - $oldCost);
                $resp = [
                    'name' => 'amend-booking',
                    'status'=> 'success',
                    $costKey => $costValue
                ];
            }
        } else {
            $resp = [
                'name' => 'amend-booking',
                'status'=> 'error',
                'reason'=> 'booking id: ' . $extBookingId . ' was not found'
            ];
        }
        return $resp;
    }

    /**
     * @param int $from
     * @param int $to
     * @return float
     *
     *
     * This function calulates the price of a booking for a given time period
     */
    public static function calculatePrice(int $from, int $to) : float
    {
        $dateFormat = "Ymd";
        //create a date object
        $fromDate = Carbon::createFromFormat($dateFormat, $from,"gmt");

        $standardPrice = 22.50;
        $saturdayPrice = 22.50 * 0.95;
        $sundayPrice = 22.50 * 0.90;

        $price = 0;
        while(((int) $fromDate->format($dateFormat)) <=   $to) {
            if($fromDate->isSaturday()) {
                $price += $saturdayPrice;
            } elseif($fromDate->isSunday()){
                $price += $sundayPrice;
            } else {
                $price += $standardPrice;
            }
            $fromDate->addDay(1);
        }
        return $price;
    }

    /**
     * @return string
     *
     * a wrapper function that calls a function that generates a random string
     * it also checks to see if the string is already present: if so it creates
     * a new string
     */
    public static function createExtId() : string
    {
        $check = 1;
        $extId = "";
        while($check){
            $extId = self::extIdHelper();
            $check = self::where('external_id', $extId)->count();
        }

        return $extId;
    }

    /**
     * @return string
     * A function that generates a random string of characters
     * used to create a user facing booking id
     */
    private static function extIdHelper() : string
    {
        $length = 16;

        $charList =  [];
        for($ints = 48; $ints < 58; $ints++){
            $charList[] = chr($ints);
        }
        for($uppers = 65; $uppers < 91; $uppers++){
            $charList[] = chr($uppers);
        }
        $count  = count($charList);
        $randStr= "";

        for($i = 0; $i < $length; $i++){
            $rand   = rand(0, ($count - 1));
            $randStr.= $charList[$rand];
        }
        return $randStr;
    }

    /**
     * @param string $dateString
     * @return int|false
     *
     * remove any common patterns from the datestring and
     * return the date as an integer or false if unsuccessful
     */
    public static function formatDate(string $dateString) : int|false
    {
        $length = 8;
        $dateAsString = str_replace(["/", "-", " 00:00:00"], "", $dateString);
        if(is_numeric($dateAsString) && strlen($dateAsString) == $length){
            return (int) $dateAsString;
        } else {
            return false;
        }
    }

    /**
     * @param int $from
     * @param int $to
     * @return bool
     *
     * Ensure that we can't create bookings in the past
     */
    public static function validateDates(int $from, int $to) : bool
    {
        $now = (int) Carbon::now()->format("Ymd");
        return ($to >= $from && $from >= $now);
    }

}
