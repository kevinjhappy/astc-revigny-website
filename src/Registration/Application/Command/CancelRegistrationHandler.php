<?php
namespace App\Registration\Application\Command;
use App\Registration\Domain\RegistrationRepository;
use App\Registration\Domain\RegistrationStatus;
use App\Shared\Domain\ValueObject\Uuid;
final class CancelRegistrationHandler {
    public function __construct(private RegistrationRepository $repo) {}
    public function __invoke(CancelRegistrationCommand $c): void {
        $r = $this->repo->get(Uuid::fromString($c->id)) ?? throw new \DomainException('not found');
        $wasConfirmed = $r->status() === RegistrationStatus::CONFIRMED;
        $r->cancel(); $this->repo->save($r);
        if ($wasConfirmed) {
            $next = $this->repo->firstWaitingList($r->tournamentId());
            if ($next) { $next->promoteToPending(); $this->repo->save($next); }
        }
    }
}
