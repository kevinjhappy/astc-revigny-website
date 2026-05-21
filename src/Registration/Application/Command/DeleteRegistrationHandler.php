<?php

namespace App\Registration\Application\Command;

use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteRegistrationHandler
{
    public function __construct(private RegistrationRepository $repo) {}

    public function __invoke(DeleteRegistrationCommand $command): void
    {
        $registration = $this->repo->get(Uuid::fromString($command->id));
        if ($registration) {
            $this->repo->remove($registration);
        }
    }
}
