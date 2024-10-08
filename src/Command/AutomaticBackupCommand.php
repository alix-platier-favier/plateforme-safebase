<?php

namespace App\Command;

use App\Entity\Database;
use App\Entity\Backup;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:autoBackup',
    description: 'Automatically backup a database by its ID',
    aliases: ['app:backup-auto', 'app:db-backup']
)]
class AutomaticBackupCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('databaseId', InputArgument::REQUIRED, 'ID of the database to backup');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $databaseId = $input->getArgument('databaseId');

        $database = $this->entityManager->getRepository(Database::class)->find($databaseId);

        if (!$database) {
            $io->error(sprintf('Database with ID %d not found.', $databaseId));
            return Command::FAILURE;
        }

        $backup = new Backup();
        $backup->setAssociatedDatabase($database);
        $backup->setCreatedAt(new \DateTime());

        $backupDir = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'backup';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backupFileName = sprintf('backup_%s_%s.sql', $database->getDbname(), (new \DateTime())->format('YmdHis'));
        $backupFilePath = sprintf('%s%s%s', $backupDir, DIRECTORY_SEPARATOR, $backupFileName);

        $io->note('Backup file path: ' . $backupFilePath);

        $command = sprintf(
            'mysqldump -h %s -P %d -u %s -p%s %s > %s',
            escapeshellarg($database->getHost()),
            $database->getPort(),
            escapeshellarg($database->getUsername()),
            escapeshellarg($database->getPassword()),
            escapeshellarg($database->getDbname()),
            escapeshellarg($backupFilePath)
        );

        exec($command, $outputLines, $resultCode);

        if ($resultCode !== 0) {
            $io->error('Error during database backup. Command output: ' . implode("\n", $outputLines));
            return Command::FAILURE;
        }

        $backup->setFilename($backupFileName);
        $this->entityManager->persist($backup);
        $this->entityManager->flush();

        $io->success(sprintf('Database backup created successfully for database ID %d.', $databaseId));
        return Command::SUCCESS;
    }
}
