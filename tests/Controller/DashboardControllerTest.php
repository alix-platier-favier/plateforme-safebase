<?php

namespace App\Tests\Controller;

use App\Entity\Database;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends WebTestCase
{
    public function testAddDatabase()
    {
        // Créez un client de test
        $client = static::createClient();

        // Créez un mock pour l'EntityManagerInterface
        $entityManager = $this->createMock(EntityManagerInterface::class);

        // Simulation de la méthode persist
        $entityManager->expects($this->once())
            ->method('persist')
            ->will($this->returnCallback(function (Database $database) {
                $this->assertEquals('Test Database', $database->getName());
            }));

        // S'assurer que flush est appelé une fois
        $entityManager->expects($this->once())
            ->method('flush');

        // Injectez le mock EntityManager dans le conteneur
        $client->getContainer()->set(EntityManagerInterface::class, $entityManager);

        // Données à envoyer dans la requête
        $data = [
            'name' => 'Test Database',
            'host' => 'localhost',
            'port' => 3306,
            'username' => 'root',
            'password' => 'root',
            'dbname' => 'test_db'
        ];

        // Envoie une requête POST
        $client->request('POST', '/add-database', $data);

        // Vérification de la réponse
        $this->assertResponseStatusCodeSame(302); // En cas de redirection après ajout
    }
}
