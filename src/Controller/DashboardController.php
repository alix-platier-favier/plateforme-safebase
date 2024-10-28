<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Database;
use App\Repository\DatabaseRepository;
use App\Entity\Backup;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(DatabaseRepository $databaseRepository): Response
    {
        $databases = $databaseRepository->findAll();

        return $this->render('dashboard/index.html.twig', [
            'databases' => $databases,
        ]);
    }

    #[Route('/add-database', name: 'add_database', methods: ['POST'])]
    public function addDatabase(Request $request, EntityManagerInterface $entityManager): Response
    {
        // $database = new Database();
        // $database->setName($request->request->get('name'));
        // $database->setHost($request->request->get('host'));
        // $database->setPort($request->request->get('port'));
        // $database->setUsername($request->request->get('username'));
        // $database->setPassword($request->request->get('password'));
        // $database->setDbname($request->request->get('dbname'));

        // $createDbCommand = sprintf(
        //     'mysql -h %s -P %d -u %s -p%s -e "CREATE DATABASE IF NOT EXISTS %s"',
        //     escapeshellarg($database->getHost()),
        //     $database->getPort(),
        //     escapeshellarg($database->getUsername()),
        //     escapeshellarg($database->getPassword()),
        //     escapeshellarg($database->getDbname())
        // );

        // exec($createDbCommand, $output, $returnVar);

        // if ($returnVar !== 0) {
        //     $this->addFlash('danger', 'Failed to create the database. Please check the connection details.');
        //     return $this->redirectToRoute('dashboard');
        // }

        // $entityManager->persist($database);
        // $entityManager->flush();

        // $this->populateMockData($database);

        // $this->addFlash('success', 'Database created and populated successfully.');
        // return $this->redirectToRoute('dashboard');

        $database = new Database();
        $database->setName($request->request->get('name'));
        $database->setHost($request->request->get('host'));
        $database->setPort($request->request->get('port'));
        $database->setUsername($request->request->get('username'));
        $database->setPassword($request->request->get('password'));
        $database->setDbname($request->request->get('dbname'));

        $entityManager->persist($database);
        $entityManager->flush();

        $this->populateMockData($database);

        $this->addFlash('success', 'Database created and populated successfully.');
        return $this->redirectToRoute('dashboard');
    }

    private function populateMockData(Database $database): void
    {
        $mockDataCommand = sprintf(
            'mysql -h %s -P %d -u %s -p%s %s -e "CREATE TABLE IF NOT EXISTS test_data (id INT PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255), value INT); INSERT INTO test_data (name, value) VALUES (\'Sample 1\', 100), (\'Sample 2\', 200);"',
            escapeshellarg($database->getHost()),
            $database->getPort(),
            escapeshellarg($database->getUsername()),
            escapeshellarg($database->getPassword()),
            escapeshellarg($database->getDbname())
        );

        exec($mockDataCommand);
    }

    #[Route('/backup-database/{id}', name: 'backup_database')]
    public function backupDatabase(Database $database, EntityManagerInterface $entityManager): Response
    {
        $backup = new Backup();
        $backup->setAssociatedDatabase($database);
        $backup->setCreatedAt(new \DateTime());

        $backupFileName = sprintf('backup_%s_%s.sql', $database->getDbname(), (new \DateTime())->format('YmdHis'));
        $backupFilePath = sprintf('backup\%s', $backupFileName);

        $command = sprintf(
            'mysqldump -h %s -P %d -u %s -p%s %s > %s',
            escapeshellarg($database->getHost()),
            $database->getPort(),
            escapeshellarg($database->getUsername()),
            escapeshellarg($database->getPassword()),
            escapeshellarg($database->getDbname()),
            escapeshellarg($backupFilePath)
        );

        exec($command);

        $backup->setFilename($backupFileName);
        $entityManager->persist($backup);
        $entityManager->flush();

        $this->addFlash('success', 'Database backup created successfully.');

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/delete-database/{id}', name: 'delete_database')]
    public function deleteDatabase(Database $database, EntityManagerInterface $entityManager): Response
    {
        $backupRepository = $entityManager->getRepository(Backup::class);
        $backups = $backupRepository->findBy(['associatedDatabase' => $database]);

        foreach ($backups as $backup) {
            $filePath = sprintf('backup/%s', $backup->getFilename());
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $entityManager->remove($backup);
        }

        $entityManager->remove($database);
        $entityManager->flush();

        $deleteDbCommand = sprintf(
            'mysql -h %s -P %d -u %s -p%s -e "DROP DATABASE IF EXISTS %s"',
            escapeshellarg($database->getHost()),
            $database->getPort(),
            escapeshellarg($database->getUsername()),
            escapeshellarg($database->getPassword()),
            escapeshellarg($database->getDbname())
        );

        exec($deleteDbCommand, $output, $returnVar);

        if ($returnVar !== 0) {
            $this->addFlash('danger', 'Failed to delete the database. Please check the connection details.');
            return $this->redirectToRoute('dashboard');
        }

        $this->addFlash('success', 'Database and associated backups deleted successfully.');

        return $this->redirectToRoute('dashboard');
    }


    #[Route('/restore-database/{id}', name: 'restore_database', methods: ['POST'])]
    public function restoreDatabase(Request $request, Database $database, EntityManagerInterface $entityManager): Response
    {
        $backupFileName = $request->request->get('backupFile');

        if (!$backupFileName) {
            $this->addFlash('danger', 'No backup file selected for restoration.');
            return $this->redirectToRoute('dashboard');
        }

        $backup = $entityManager->getRepository(Backup::class)->findOneBy(['filename' => $backupFileName]);

        if (!$backup) {
            $this->addFlash('danger', 'Backup file not found.');
            return $this->redirectToRoute('dashboard');
        }

        $backupFilePath = sprintf('backup/%s', $backupFileName);

        if (file_exists($backupFilePath)) {
            $command = sprintf(
                'mysql -h %s -P %d -u %s -p%s %s < %s',
                escapeshellarg($database->getHost()),
                $database->getPort(),
                escapeshellarg($database->getUsername()),
                escapeshellarg($database->getPassword()),
                escapeshellarg($database->getDbname()),
                escapeshellarg($backupFilePath)
            );

            exec($command, $output, $returnVar);

            if ($returnVar !== 0) {
                $this->addFlash('danger', 'An error occurred during database restoration.');
                return $this->redirectToRoute('dashboard');
            }

            $backupDate = $backup->getCreatedAt();
            $subsequentBackups = $entityManager->getRepository(Backup::class)->findBy([
                'associatedDatabase' => $database
            ]);

            foreach ($subsequentBackups as $subBackup) {
                if ($subBackup->getCreatedAt() > $backupDate) {
                    $filePath = sprintf('backup/%s', $subBackup->getFilename());
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }

                    $entityManager->remove($subBackup);
                }
            }
            $entityManager->flush();

            $this->addFlash('success', 'Database restored successfully.');
        } else {
            $this->addFlash('danger', 'Backup file does not exist.');
        }

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/schedule-backup', name: 'schedule_backup', methods: ['POST'])]
    public function scheduleBackup(Request $request): Response
    {
        $databaseId = $request->request->get('database_id');
        $frequency = $request->request->get('backupFrequency');

        $schtasksFrequency = match ($frequency) {
            'minute' => 'MINUTE',
            'hour' => 'HOURLY',
            'day' => 'DAILY',
            'week' => 'WEEKLY',
            'month' => 'MONTHLY',
            default => 'DAILY',
        };

        $command = sprintf(
            'schtasks /create /tn "SymfonyAutoBackupDatabase_%s" /tr "php bin/console app:autoBackup %d" /sc %s /f',
            $databaseId,
            $databaseId,
            $schtasksFrequency
        );

        $output = null;
        $resultCode = null;
        exec($command, $output, $resultCode);

        if ($resultCode === 0) {
            $this->addFlash('success', 'La sauvegarde automatique a été planifiée avec succès pour la base de données ID: ' . $databaseId);
        } else {
            $this->addFlash('danger', 'Erreur lors de la planification de la sauvegarde automatique.');
        }

        return $this->redirectToRoute('dashboard');
    }

}
