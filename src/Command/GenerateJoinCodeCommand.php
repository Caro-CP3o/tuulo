<?php

namespace App\Command;

use App\Repository\FamilyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-join-code',
    description: 'Generate a join code for a family',
)]
class GenerateJoinCodeCommand extends Command
{
    public function __construct(
        private FamilyRepository $familyRepository,
        private EntityManagerInterface $entityManager,
        private ParameterBagInterface $params,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            //     ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            //     ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
            // ;
            ->addArgument('familyId', InputArgument::REQUIRED, 'The ID of the family');
    }

    // protected function execute(InputInterface $input, OutputInterface $output): int
    // {
    //     $io = new SymfonyStyle($input, $output);
    //     $arg1 = $input->getArgument('arg1');

    //     if ($arg1) {
    //         $io->note(sprintf('You passed an argument: %s', $arg1));
    //     }

    //     if ($input->getOption('option1')) {
    //         // ...
    //     }

    //     $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

    //     return Command::SUCCESS;
    // }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $familyId = $input->getArgument('familyId');
        $family = $this->familyRepository->find($familyId);

        if (!$family) {
            $output->writeln('<error>Family not found.</error>');
            return Command::FAILURE;
        }

        $joinCode = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $family->setJoinCode($joinCode);
        $this->entityManager->flush();

        $baseUrl = rtrim($this->params->get('app.frontend_url'), '/');
        $link = $baseUrl . '/join?familyCode=' . $joinCode;

        $output->writeln("Join code generated: <info>$joinCode</info>");
        // $output->writeln("Invitation link: https://tuulo.app/join?familyCode=$joinCode");
        $output->writeln("Invitation link: $link");

        return Command::SUCCESS;
    }
}
