<?php

namespace App\Registration\Application\Command;

use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class ResetRegistrationHandler
{
    public function __construct(private RegistrationRepository $repo) {}

    public function __invoke(ResetRegistrationCommand $command): void
    {
        $registration = $this->repo->get(Uuid::fromString($command->id))
            ?? throw new \DomainException('not found');
        $registration->resetToPending();
        $this->repo->save($registration);
    }
}
