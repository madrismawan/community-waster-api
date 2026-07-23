<?php

use App\Models\Household;
use MongoDB\BSON\ObjectId;

it('lists and filters active households with pagination metadata', function () {
    $matchingHousehold = testCreateHousehold([
        'owner_name' => 'Made Rismawan',
        'address' => 'Jalan Kenanga',
        'block' => 'B',
        'no' => '01',
    ]);
    testCreateHousehold([
        'owner_name' => 'Komang Putra',
        'address' => 'Jalan Melati',
        'block' => 'A',
        'no' => '02',
    ]);
    $deletedHousehold = testCreateHousehold([
        'owner_name' => 'Made Rismawan Deleted',
        'address' => 'Jalan Kenanga',
        'block' => 'B',
        'no' => '01',
    ]);
    $deletedHousehold->delete();

    $this->getJson('/api/households?search=made&block=b&no=01&per_page=1&page=1')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Households retrieved successfully.')
        ->assertJsonPath('data.0.id', (string) $matchingHousehold->getKey())
        ->assertJsonPath('data.0.owner_name', 'Made Rismawan')
        ->assertJsonPath('meta.current_page', 1)
        ->assertJsonPath('meta.last_page', 1)
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('meta.from', 1)
        ->assertJsonPath('meta.to', 1)
        ->assertJsonPath('errors', null)
        ->assertJsonCount(1, 'data');
});

it('returns an empty paginated household list', function () {
    $this->getJson('/api/households')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', [])
        ->assertJsonPath('meta.total', 0)
        ->assertJsonPath('meta.from', null)
        ->assertJsonPath('meta.to', null);
});

it('validates household list filters', function () {
    $this->getJson('/api/households?per_page=101&page=0')
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonStructure([
            'errors' => ['per_page', 'page'],
        ]);
});

it('creates a household', function () {
    $response = $this->postJson('/api/households', [
        'owner_name' => 'Kadek Santika',
        'address' => 'Jalan Mawar No. 10',
        'block' => 'C',
        'no' => '07',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Household created successfully.')
        ->assertJsonPath('data.owner_name', 'Kadek Santika')
        ->assertJsonPath('data.address', 'Jalan Mawar No. 10')
        ->assertJsonPath('data.block', 'C')
        ->assertJsonPath('data.no', '07')
        ->assertJsonPath('errors', null)
        ->assertJsonStructure([
            'data' => ['id', 'created_at', 'updated_at'],
        ]);

    $household = Household::query()->find($response->json('data.id'));

    expect($household)->not->toBeNull()
        ->and($household->owner_name)->toBe('Kadek Santika')
        ->and($household->address)->toBe('Jalan Mawar No. 10');
});

it('validates required household data when creating a household', function () {
    $this->postJson('/api/households', [
        'block' => str_repeat('B', 21),
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonStructure([
            'errors' => ['owner_name', 'address', 'block'],
        ]);
});

it('shows a household', function () {
    $household = testCreateHousehold([
        'owner_name' => 'Nyoman Surya',
        'address' => 'Jalan Anggrek',
        'block' => 'D',
        'no' => '03',
    ]);

    $this->getJson('/api/households/'.$household->getKey())
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Household retrieved successfully.')
        ->assertJsonPath('data.id', (string) $household->getKey())
        ->assertJsonPath('data.owner_name', 'Nyoman Surya')
        ->assertJsonPath('data.address', 'Jalan Anggrek')
        ->assertJsonPath('data.block', 'D')
        ->assertJsonPath('data.no', '03');
});

it('returns not found when showing a missing household', function () {
    $this->getJson('/api/households/'.new ObjectId)
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Resource not found.')
        ->assertJsonPath('errors', null);
});

it('updates a household with partial data', function () {
    $household = testCreateHousehold([
        'owner_name' => 'Original Owner',
        'address' => 'Original Address',
        'block' => 'A',
        'no' => '01',
    ]);

    $this->putJson('/api/households/'.$household->getKey(), [
        'owner_name' => 'Updated Owner',
        'address' => 'Updated Address',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Household updated successfully.')
        ->assertJsonPath('data.id', (string) $household->getKey())
        ->assertJsonPath('data.owner_name', 'Updated Owner')
        ->assertJsonPath('data.address', 'Updated Address')
        ->assertJsonPath('data.block', 'A')
        ->assertJsonPath('data.no', '01');

    $household->refresh();

    expect($household->owner_name)->toBe('Updated Owner')
        ->and($household->address)->toBe('Updated Address');
});

it('validates household updates', function () {
    $household = testCreateHousehold();

    $this->putJson('/api/households/'.$household->getKey(), [
        'owner_name' => '',
        'address' => ['invalid'],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonStructure([
            'errors' => ['owner_name', 'address'],
        ]);
});

it('returns not found when updating a missing household', function () {
    $this->putJson('/api/households/'.new ObjectId, [
        'owner_name' => 'Unknown Owner',
    ])
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Resource not found.');
});

it('soft deletes a household', function () {
    $household = testCreateHousehold();
    $id = (string) $household->getKey();

    $this->deleteJson('/api/households/'.$id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Household deleted successfully.')
        ->assertJsonPath('data', [])
        ->assertJsonPath('errors', null);

    expect(Household::query()->find($id))->toBeNull();

    $trashedHousehold = Household::withTrashed()->find($id);

    expect($trashedHousehold)->not->toBeNull()
        ->and($trashedHousehold->trashed())->toBeTrue();

    $this->getJson('/api/households/'.$id)
        ->assertNotFound()
        ->assertJsonPath('message', 'Resource not found.');
});

it('returns not found when deleting a missing household', function () {
    $this->deleteJson('/api/households/'.new ObjectId)
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Resource not found.');
});

it('restores a soft-deleted household', function () {
    $household = testCreateHousehold([
        'owner_name' => 'Restored Owner',
    ]);
    $id = (string) $household->getKey();
    $household->delete();

    $this->putJson('/api/households/'.$id.'/restore')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Household restored successfully.')
        ->assertJsonPath('data.id', $id)
        ->assertJsonPath('data.owner_name', 'Restored Owner')
        ->assertJsonPath('errors', null);

    $restoredHousehold = Household::query()->find($id);

    expect($restoredHousehold)->not->toBeNull()
        ->and($restoredHousehold->deleted_at)->toBeNull();
});

it('returns not found when restoring an active household', function () {
    $household = testCreateHousehold();

    $this->putJson('/api/households/'.$household->getKey().'/restore')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Resource not found.');
});
