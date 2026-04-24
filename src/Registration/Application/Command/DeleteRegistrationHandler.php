<?php
namespace App\Registration\Application\Command;
use App\Registration\Domain\RegistrationRepository;
use App\Shared\Domain\ValueObject\Uuid;
final class DeleteRegistrationHandler {
    public function __construct(private RegistrationRepository $repo) {}
    public function __invoke(DeleteRegistrationCommand $c): void {
        $r = $this->repo->get(Uuid::fromString($c->id));
        if ($r) $this->repo->remove($r);
    }
}
