<?php
namespace App\Registration\Application\Command;
use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;
final class ResetRegistrationHandler {
    public function __construct(private RegistrationRepository $repo) {}
    public function __invoke(ResetRegistrationCommand $c): void {
        $r = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $r->resetToPending();
        $this->repo->save($r);
    }
}
