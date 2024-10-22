<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    /**
     * @Route("/login", name="login")
     */
    public function login(OAuth2ClientInterface $client): Response
    {
        $targetPath = $this->generateUrl('azure_oauth_callback');
        $response = $client->redirect(['openid'], ['target_path' => $targetPath]);

        return $response;
    }
}

