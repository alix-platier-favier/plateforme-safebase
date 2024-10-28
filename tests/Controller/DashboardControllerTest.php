<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
    public function testAddDatabase()
    {
        // Créer un client pour simuler une requête HTTP
        $client = static::createClient();
        
        // Définir les données de test pour l'ajout de la base de données
        $data = [
            'name' => 'TestDatabase',
            'host' => 'localhost',
            'port' => 3306,
            'username' => 'test_user',
            'password' => 'test_password',  // password ne peut pas être vide
            'dbname' => 'test_dbname',
        ];
        
        // Faire une requête POST pour ajouter une base de données
        $client->request('POST', '/add-database', $data);
        
        // Vérifier que la réponse est un succès (status HTTP 200 ou 201)
        $this->assertEquals(201, $client->getResponse()->getStatusCode());

        // Optionnel : Vérifier le contenu de la réponse
        $this->assertStringContainsString('Database successfully added', $client->getResponse()->getContent());
    }
}
