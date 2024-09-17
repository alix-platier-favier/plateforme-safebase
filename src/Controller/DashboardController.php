<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
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
}
