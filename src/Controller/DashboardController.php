<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Database;
use App\Repository\DatabaseRepository;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'dashboard')]
    public function index(DatabaseRepository $databaseRepository): Response
    {
        $databases = $databaseRepository->findAll();

        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'DashboardController',
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
    
    #[Route('/save-database/{id}', name: 'save_database', methods: ['POST'])]
    public function saveDatabase(int $id, EntityManagerInterface $entityManager): Response
    {
        $database = $entityManager->getRepository(Database::class)->find($id);

        if ($database) {
            $dbname = $database->getDbname();
            $backupFile = "/path/to/backups/{$dbname}_" . date('Ymd_His') . ".sql";

            // Use mysqldump to save the database
            $command = "mysqldump -u {$database->getUsername()} -p{$database->getPassword()} {$dbname} > {$backupFile}";
            system($command);
        }

        $this->addFlash('success', 'Database saved successfully!');

        return $this->redirectToRoute('dashboard');
    }

    #[Route('/restore-database/{id}', name: 'restore_database', methods: ['POST'])]
    public function restoreDatabase(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $database = $entityManager->getRepository(Database::class)->find($id);

        if ($database) {
            $dbname = $database->getDbname();
            $backupFile = $request->request->get('backup_file'); 

            $command = "mysql -u {$database->getUsername()} -p{$database->getPassword()} {$dbname} < {$backupFile}";
            system($command);
        }

        $this->addFlash('success', 'Database restored successfully!');

        return $this->redirectToRoute('dashboard');
    }


}
