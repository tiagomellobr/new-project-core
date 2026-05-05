<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Cria um novo usuário no sistema.',
)]
class CreateUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'Nome completo do usuário')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'E-mail do usuário')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Senha do usuário')
            ->addOption('admin', null, InputOption::VALUE_NONE, 'Conceder papel ROLE_ADMIN')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Criar novo usuário');

        $name = $input->getOption('name') ?? $io->ask('Nome completo', validator: static function (?string $v): string {
            if (empty(trim((string) $v))) {
                throw new \RuntimeException('O nome não pode ser vazio.');
            }
            return $v;
        });

        $email = $input->getOption('email') ?? $io->ask('E-mail', validator: static function (?string $v): string {
            if (!filter_var($v, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('Informe um e-mail válido.');
            }
            return $v;
        });

        $password = $input->getOption('password') ?? $io->askHidden('Senha (mínimo 6 caracteres)', validator: static function (?string $v): string {
            if (strlen((string) $v) < 6) {
                throw new \RuntimeException('A senha deve ter ao menos 6 caracteres.');
            }
            return $v;
        });

        $isAdmin = (bool) $input->getOption('admin');

        $existing = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing !== null) {
            $io->error(sprintf('Já existe um usuário com o e-mail "%s".', $email));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setIsVerified(true);

        if ($isAdmin) {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Usuário "%s" (%s) criado com sucesso!', $name, $email));
        $io->table(
            ['Campo', 'Valor'],
            [
                ['Nome', $name],
                ['E-mail', $email],
                ['Admin', $isAdmin ? 'Sim' : 'Não'],
                ['E-mail verificado', 'Sim'],
            ]
        );

        return Command::SUCCESS;
    }
}
