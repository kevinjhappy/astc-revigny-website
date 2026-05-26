<?php

namespace App\Registration\Application\Command;

use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelRegistrationHandler
{
    public function __construct(private RegistrationRepository $repo) {}

    public function __invoke(CancelRegistrationCommand $command): void
    {
        $registration = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('not found');
        $wasConfirmed = $registration->status() === RegistrationStatus::CONFIRMED;
        $registration->cancel();
        $this->repo->save($registration);
        if ($wasConfirmed) {
            $next = $this->repo->firstWaitingList($registration->tournamentId());
            if ($next) {
                $next->promoteToPending();
                $this->repo->save($next);
            }
        }
    }
}
