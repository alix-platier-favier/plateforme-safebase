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
}
