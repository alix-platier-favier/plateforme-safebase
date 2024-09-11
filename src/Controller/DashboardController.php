<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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
}
