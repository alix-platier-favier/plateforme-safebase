<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Database;
use App\Entity\Backup;
use Symfony\Component\Process\Process;
use App\Repository\DatabaseRepository;
use App\Repository\BackupRepository;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DashboardController extends AbstractController
{


    #[Route('/', name: 'dashboard')]
    public function index(DatabaseRepository $databaseRepository, BackupRepository $backupRepository): Response
    {
        $databases = $databaseRepository->findAll();
        $backups = $backupRepository->findAll(); 

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
            'databases' => $databases,
            'backups' => $backups, 
        ]);
    }

    #[Route('/add-database', name: 'add_database', methods: ['POST'])]
    public function addDatabase(Request $request, EntityManagerInterface $entityManager): Response
    {
        $database = new Database();
        $database->setName($request->request->get('name'));
        $database->setHost($request->request->get('host'));
        $database->setPort($request->request->get('port'));
        $database->setUsername($request->request->get('username'));
        $database->setPassword($request->request->get('password'));
        $database->setDbname($request->request->get('dbname'));

        $entityManager->persist($database);
        $entityManager->flush();

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/delete-database/{id}', name: 'delete_database', methods: ['POST'])]
    public function deleteDatabase(int $id, DatabaseRepository $databaseRepository, EntityManagerInterface $entityManager): Response
    {
        $database = $databaseRepository->find($id);

        if ($database) {
            $entityManager->remove($database);
            $entityManager->flush();
        }

        $this->addFlash('danger', 'Database deleted successfully!');

        return $this->redirectToRoute('dashboard');
    }
    
    #[Route('/backup/{id}', name: 'backup_database', methods: ['POST'])]
    public function backupDatabase(Database $database, EntityManagerInterface $entityManager): Response
    {
        $filename = sprintf('backup_%d_%s.sql', $database->getId(), date('YmdHis'));

        $command = sprintf(
            'mysqldump -u %s -p%s %s > %s',
            escapeshellarg($database->getUsername()),
            escapeshellarg($database->getPassword()),
            escapeshellarg($database->getDbname()),
            escapeshellarg($filename)
        );

        $process = new Process(['bash', '-c', $command]);

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new \RuntimeException($exception->getMessage());
        }

        $backup = new Backup();
        $backup->setFilename($filename);
        $backup->setCreatedAt(new \DateTime());
        $backup->setAssociatedDatabase($database);

        $entityManager->persist($backup);
        $entityManager->flush();

        return $this->redirectToRoute('dashboard');
    }
}
