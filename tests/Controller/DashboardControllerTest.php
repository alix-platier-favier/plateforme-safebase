<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Database;
use App\Repository\DatabaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\BackupRepository;

class DashboardControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Dashboard');
    }

    public function testAddDatabase()
    {
        $client = static::createClient();
        $client->request('POST', '/add-database', [
            'name' => 'TestDB',
            'host' => 'localhost',
            'port' => '3306',
            'username' => 'test_user',
            'password' => 'password',
            'dbname' => 'testdb',
        ]);

        $this->assertResponseRedirects('/');

        // Vérifiez que la base de données a été créée
        $databaseRepository = $this->getContainer()->get(DatabaseRepository::class);
        $database = $databaseRepository->findOneBy(['name' => 'TestDB']);

        $this->assertNotNull($database);
        $this->assertEquals('TestDB', $database->getName());
    }
}
