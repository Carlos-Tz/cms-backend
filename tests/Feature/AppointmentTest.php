<?php

use App\Models\Appointment;
use App\Models\Site;
use App\Models\Station;
use App\Models\User;
use Database\Seeders\GnuBlueprintSeeder;
use Database\Seeders\GnuVehicularBlueprintSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tymon\JWTAuth\Facades\JWTAuth;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Frozen on a Monday so «tomorrow», «Sunday» and «yesterday» are the same
    // days no matter when the suite runs.
    Carbon::setTestNow('2026-08-24 09:00:00');

    $this->seed(GnuVehicularBlueprintSeeder::class);
    $this->site = Site::where('slug', 'gnu-vehicular')->firstOrFail();
    $this->morelia = $this->site->stations()->where('name', 'Estación Morelia')->firstOrFail();
    $this->uruapan = $this->site->stations()->where('name', 'Estación Uruapan')->firstOrFail();
    $this->token = JWTAuth::fromUser(User::factory()->create());
});

/**
 * A valid booking payload for the frozen Monday.
 *
 * @return array<string, mixed>
 */
function booking(Station $station, string $slot = '10:00-12:00', string $date = '2026-08-24'): array
{
    return [
        'station_id' => $station->id,
        'date' => $date,
        'slot' => $slot,
        'name' => 'Ana Ruiz',
        'email' => 'ana@example.com',
        'phone' => '+52 443 111 2222',
        'vehicle_plate' => 'ABC-12-34',
        'vehicle_description' => 'Nissan Versa 2020',
    ];
}

it('offers the three slots of a working day', function () {
    $this->getJson("/api/public/gnu-vehicular/appointments/availability?station={$this->morelia->id}&date=2026-08-24")
        ->assertOk()
        ->assertJsonPath('data.station.name', 'Estación Morelia')
        ->assertJsonCount(3, 'data.slots')
        ->assertJsonPath('data.slots.0.slot', '10:00-12:00')
        ->assertJsonPath('data.slots.0.available', true)
        ->assertJsonPath('data.slots.1.slot', '12:00-14:00')
        ->assertJsonPath('data.slots.2.slot', '14:00-16:00');
});

it('marks a booked slot as unavailable', function () {
    Appointment::factory()->for($this->site)->for($this->morelia)
        ->create(['date' => '2026-08-24', 'slot' => '12:00-14:00']);

    $this->getJson("/api/public/gnu-vehicular/appointments/availability?station={$this->morelia->id}&date=2026-08-24")
        ->assertOk()
        ->assertJsonPath('data.slots.0.available', true)
        ->assertJsonPath('data.slots.1.available', false)
        ->assertJsonPath('data.slots.2.available', true);
});

it('closes Sundays and past dates without failing', function (string $date) {
    $this->getJson("/api/public/gnu-vehicular/appointments/availability?station={$this->morelia->id}&date={$date}")
        ->assertOk()
        ->assertJsonCount(0, 'data.slots');
})->with([
    '2026-08-30',   // Sunday
    '2026-08-23',   // yesterday
]);

it('404s on a station of another site or an inactive one', function () {
    $this->seed(GnuBlueprintSeeder::class);
    $other = Site::where('slug', 'gnu')->firstOrFail()->stations()->firstOrFail();

    $this->getJson("/api/public/gnu-vehicular/appointments/availability?station={$other->id}&date=2026-08-24")
        ->assertNotFound();

    $this->morelia->update(['is_active' => false]);

    $this->getJson("/api/public/gnu-vehicular/appointments/availability?station={$this->morelia->id}&date=2026-08-24")
        ->assertNotFound();
});

it('books a slot and confirms which one', function () {
    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia))
        ->assertCreated()
        ->assertJsonPath('data.date', '2026-08-24')
        ->assertJsonPath('data.slot', '10:00-12:00')
        ->assertJsonPath('data.station', 'Estación Morelia')
        ->assertJsonPath('meta.message', 'Tu cita quedó registrada. Te esperamos.');

    $appointment = Appointment::firstOrFail();

    expect($appointment->site_id)->toBe($this->site->id)
        ->and($appointment->vehicle_plate)->toBe('ABC-12-34');

    $this->getJson("/api/public/gnu-vehicular/appointments/availability?station={$this->morelia->id}&date=2026-08-24")
        ->assertJsonPath('data.slots.0.available', false);
});

it('rejects a slot already taken at that station', function () {
    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia))->assertCreated();

    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slot' => 'Ese horario ya está ocupado. Elige otro.']);

    expect(Appointment::count())->toBe(1);
});

it('allows the same slot at the other station on the same day', function () {
    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia))->assertCreated();
    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->uruapan))->assertCreated();

    expect(Appointment::count())->toBe(2);
});

it('rejects a Sunday, a past date and an unknown slot, in Spanish', function () {
    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia, date: '2026-08-30'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date' => 'No hay verificaciones en domingo. Elige un día de lunes a sábado.']);

    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia, date: '2026-08-23'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['date' => 'La fecha ya pasó. Elige un día a partir de hoy.']);

    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia, slot: '16:00-18:00'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['slot' => 'El horario seleccionado no existe.']);

    expect(Appointment::count())->toBe(0);
});

it('rejects a booking at a station of another site', function () {
    $this->seed(GnuBlueprintSeeder::class);
    $other = Site::where('slug', 'gnu')->firstOrFail()->stations()->firstOrFail();

    $this->postJson('/api/public/gnu-vehicular/appointments', booking($other))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['station_id' => 'La estación seleccionada no está disponible.']);
});

it('rejects a booking without plates', function () {
    $payload = booking($this->morelia);
    unset($payload['vehicle_plate']);

    $this->postJson('/api/public/gnu-vehicular/appointments', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['vehicle_plate' => 'Escribe las placas de tu vehículo.']);
});

it('lists the appointment in the admin agenda of its own site, soonest first', function () {
    $this->seed(GnuBlueprintSeeder::class);

    Appointment::factory()->for($this->site)->for($this->morelia)
        ->create(['date' => '2026-08-26', 'slot' => '10:00-12:00']);
    Appointment::factory()->for($this->site)->for($this->morelia)
        ->create(['date' => '2026-08-25', 'slot' => '14:00-16:00']);

    $this->withToken($this->token)
        ->getJson('/api/admin/appointments?site=gnu-vehicular')
        ->assertOk()
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.slot', '14:00-16:00')
        ->assertJsonPath('data.0.station.name', 'Estación Morelia');

    $this->withToken($this->token)
        ->getJson('/api/admin/appointments?site=gnu')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('cancels an appointment and frees its slot', function () {
    $this->postJson('/api/public/gnu-vehicular/appointments', booking($this->morelia))->assertCreated();

    $appointment = Appointment::firstOrFail();

    $this->withToken($this->token)
        ->deleteJson("/api/admin/appointments/{$appointment->id}")
        ->assertOk()
        ->assertJsonPath('meta.message', 'Cita cancelada correctamente');

    $this->getJson("/api/public/gnu-vehicular/appointments/availability?station={$this->morelia->id}&date=2026-08-24")
        ->assertJsonPath('data.slots.0.available', true);
});

it('does not let the admin create or edit an appointment', function () {
    $appointment = Appointment::factory()->for($this->site)->for($this->morelia)->create();

    $this->withToken($this->token)->postJson('/api/admin/appointments', [])->assertMethodNotAllowed();
    $this->withToken($this->token)
        ->putJson("/api/admin/appointments/{$appointment->id}", [])
        ->assertMethodNotAllowed();
});

it('blocks the admin agenda without a token', function () {
    $this->getJson('/api/admin/appointments')
        ->assertUnauthorized()
        ->assertJsonPath('meta.message', 'No autenticado');
});
