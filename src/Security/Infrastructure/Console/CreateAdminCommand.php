<?php

namespace App\Security\Infrastructure\Console;

use App\Security\Domain\AdminUser;
use App\Security\Domain\AdminUserRepository;
use App\Shared\Domain\ValueObject\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create an admin user')]
final class CreateAdminCommand extends Command
{
    public function __construct(private AdminUserRepository $repo, private UserPasswordHasherInterface $hasher)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED)->addArgument('password', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = $input->getArgument('email');
        if ($this->repo->findByEmail($email)) {
            $output->writeln('<error>Admin '.$email.' already exists</error>');

            return Command::FAILURE;
        }
        $adminUser = new AdminUser(Uuid::generate(), $email, 'placeholder');
        $adminUser->setPassword($this->hasher->hashPassword($adminUser, $input->getArgument('password')));
        $this->repo->save($adminUser);
        $output->writeln('<info>Admin '.$email.' created</info>');

        return Command::SUCCESS;
    }
}
