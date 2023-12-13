<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BookingCounter extends Model
{
    use HasFactory;

    /**
     * @param int $from
     * @param int $to
     * @param string $bookingId
     * @param string $status
     * @param string $searchStatus
     * @return void
     *
     * A function that updates the booking_counters table
     */
    public static function updateCounter(int $from, int $to,
                                           string $bookingId, string $status,
                                           string $searchStatus = 'booked') : void
    {
        $dateFormat = "Ymd";
        $mysqlFormat = "Y-m-d 00:00:00";

        //create a date object
        $fromDate = Carbon::createFromFormat($dateFormat, $from,"gmt");

        while(((int) $fromDate->format($dateFormat)) <=  $to) {
            $day = $fromDate->format($mysqlFormat);
            self::where('external_booking_id', $bookingId)
                ->where('booking_day', $day)
                ->where('status', $searchStatus)
                ->update(['status' => $status]);
            $fromDate->addDay(1);
        }
    }

    /**
     * @param int $from
     * @param int $to
     * @param string $bookingId
     * @return bool
     *
     * A function to insert a booking_counter into the table
     * This table keeps a track of how many bookings are reserved on any given day
     */
    public static function insertCounter(int $from, int $to, string $bookingId) : bool
    {
        $dateFormat = "Ymd";
        $displayFormat = "Y-m-d";
        //create a date object
        $fromDate = Carbon::createFromFormat($dateFormat,$from,"gmt");

        $bookingCounter = [];
        while(((int) $fromDate->format($dateFormat)) <=  $to) {
            $bookingCounter[] = [
                'external_booking_id' => $bookingId,
                'booking_day' => $fromDate->format($displayFormat),
                'status'    => 'booked'
            ];
            $fromDate->addDay(1);
        }
        return self::insert($bookingCounter);
    }

    /**
     * @param int $from
     * @param int $to
     * @return bool
     *
     *  A function to check if the number of bookings is full for a given time range
     */
    public static function checkFull(int $from, int $to) : bool
    {
        $days = self::checkRangeArray($from, $to);
        $full = false;
        foreach($days as $day){
            if($day > 9) {
                $full = true;
            }
        }
        return $full;
    }

    /**
     * @param int $from
     * @param int $to
     * @return array
     *
     * A function that returns an associative array of
     * [day => number_of_bookings]
     * for a given time period
     */
    public static function checkRangeArray(int $from, int $to) : array
    {
        $dateFormat = "Ymd";
        $displayFormat = "Y-m-d";
        //create a date object
        $fromDate = Carbon::createFromFormat($dateFormat, $from,"gmt");
        $days = [];
        while(((int) $fromDate->format($dateFormat)) <=   $to) {
            $result = self::where('booking_day', $fromDate->format($dateFormat))
                ->where('status', 'booked')
                ->get()
                ->count();

            $days[$fromDate->format($displayFormat)] = $result;
            $fromDate->addDay(1);
        }
        return $days;
    }
}
