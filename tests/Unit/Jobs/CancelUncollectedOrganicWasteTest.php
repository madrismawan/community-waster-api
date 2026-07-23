<?php

use App\Contract\Repositories\WasteRepositoryInterface;
use App\Jobs\CancelUncollectedOrganicWaste;
use Illuminate\Support\Carbon;

afterEach(function (): void {
    Carbon::setTestNow();
    Mockery::close();
});

it('cancels organic pickups whose pickup date was three calendar days ago', function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-25 17:00:00', 'UTC'));

    $wasteRepository = Mockery::mock(WasteRepositoryInterface::class);
    $wasteRepository->shouldReceive('cancelOverdueOrganicPickups')
        ->once()
        ->with(Mockery::on(
            fn (Carbon $cutoff): bool => $cutoff->equalTo(
                Carbon::parse('2026-07-23 15:59:59.999999', 'UTC')
            )
        ))
        ->andReturn(2);

    (new CancelUncollectedOrganicWaste)->handle($wasteRepository);
});
