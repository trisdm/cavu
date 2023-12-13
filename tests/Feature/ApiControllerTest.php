<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ApiControllerTest extends TestCase
{
    use WithFaker;
    use DatabaseTransactions;
    //booking tests
    public function test_successful_booking(): void
    {
        $carReg = 'OE342RSX';
        $bookingData = [
            'from'  => '2024-01-01',
            'to'    => '2024-01-09',
            'email' => $this->faker->email,
            'car_registration' => $carReg

        ];
        $response = $this->post('api/book', $bookingData);
        $response->assertStatus(200)
                ->assertJsonFragment([
                    'name'=> 'book',
                    'status' => 'success'
                ]);
    }

    public function test_more_than_10_bookings(): void
    {
        $carRegs = ['OE012RSX', 'OE022RSX','OE3142RSX', 'OE1342RSX',
            'OE3142RSX', 'OE3742RSX', 'AE342RSX', 'BE342RSX', 'DE342RSX','FE342RSX','AE342RSX'] ;
        $index = 0;
        while($index < 10) {
            $bookingData = [
                'from' => '2024-01-01',
                'to' => '2024-01-09',
                'email' => $this->faker->email,
                'car_registration' => $carRegs[$index++]
            ];

                $response = $this->post('api/book', $bookingData);
                $response->assertStatus(200)
                    ->assertJsonFragment([
                        'name' => 'book',
                        'status' => 'success'
                    ]);
        }

        $newData = [
            'from' => '2024-01-01',
            'to' => '2024-01-09',
            'email' => $this->faker->email,
            'car_registration' => $carRegs[$index]
        ];

        $response = $this->post('api/book', $newData);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'book',
                'status' => 'error'
            ]);
    }

    //amendment tests

    /**
     * @throws \Throwable
     */
    public function test_successful_amendment(): void
    {
        $bookingData = [
            'from' => '2024-01-01',
            'to' => '2024-01-09',
            'email' => $this->faker->email,
            'car_registration' => 'OE342RSX'
        ];

        $response = $this->post('api/book', $bookingData);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'book',
                'status' => 'success'
            ]);

        $json = $response->decodeResponseJson();

        $amendmentData = [
            'from' => '2024-01-11',
            'to' => '2024-01-19',
            'email' => $this->faker->email,
            'car_registration' => 'OE342RSX',
            'booking_id'    => $json['booking_id']
        ];

        $response = $this->post('api/amend-booking', $amendmentData);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'amend-booking',
                'status' => 'success'
            ]);
    }

    public function test_unsuccessful_amendment(): void
    {
        $bookingId = "DOES_NOT_EXIST";
        $amendmentData = [
            'from' => '2024-01-11',
            'to' => '2024-01-19',
            'email' => $this->faker->email,
            'car_registration' => 'OE342RSX',
            'booking_id'    => $bookingId
        ];

        $response = $this->post('api/amend-booking', $amendmentData);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'amend-booking',
                'reason' => 'booking id: DOES_NOT_EXIST was not found',
                'status' => 'error'
            ]);
    }

    //cancel tests

    /**
     * @throws \Throwable
     */
    public function test_successful_cancel() : void
    {
        $carReg = 'OE342RSX';
        $bookingData = [
            'from'  => '2024-01-01',
            'to'    => '2024-01-09',
            'email' => $this->faker->email,
            'car_registration' => $carReg

        ];
        $response = $this->post('api/book', $bookingData);
        $response->assertStatus(200)
            ->assertJsonFragment([
                'name'=> 'book',
                'status' => 'success'
            ]);
        $json = $response->decodeResponseJson();

        $cancelData = [
            'booking_id' => $json['booking_id']
        ];

        $cancelResponse = $this->post('api/cancel-booking', $cancelData);
        $cancelResponse->assertStatus(200)
            ->assertJsonFragment([
                'name'=> 'cancel-booking',
                'status' => 'success'
            ]);

    }

    public function test_unsuccessful_cancel() : void
    {
        $cancelData = [
            'booking_id' => 'DOES_NOT_EXIST'
        ];

        $cancelResponse = $this->post('api/cancel-booking', $cancelData);
        $cancelResponse->assertStatus(200)
            ->assertJsonFragment([
                'name'=> 'cancel-booking',
                'status' => 'error'
            ]);
    }

    //check range tests
    public function test_successful_check_range() : void
    {
        $checkData = [
            'from'  => '2024-12-01',
            'to'    => '2024-12-31'
        ];
        $checkResponse = $this->post('api/check-range', $checkData);
        $checkResponse->assertJsonFragment([
            'available_to_book' => true,
            'booking_cost'  => 681.75
        ]);
    }


    public function test_unsuccessful_check_range() : void
    {
        $checkData = [
            'from'  => '2025-12-01',
            'to'    => '2024-12-31'
        ];
        $checkResponse = $this->post('api/check-range', $checkData);
        $checkResponse->assertJsonFragment([
            'available_to_book' => false
        ]);
    }

}
