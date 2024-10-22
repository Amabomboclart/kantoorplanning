<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\GraphApiClient;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2ClientInterface;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{

    public function onAzureOAuthSuccess(
        Request $request,
        AuthenticationUtils $authenticationUtils,
        SessionInterface $session,
        ClientRegistry $clientRegistry,
        OAuth2ClientInterface $oauth2Client,
        Security $security,
        EntityManagerInterface $entityManager,
        HttpClientInterface $httpClient
    ): Response {
        $client = $clientRegistry->getClient('azure');
        $accessToken = $client->getAccessToken();
        $provider = $client->getOAuth2Provider();
        $fetched = $provider->get($provider->getRootMicrosoftGraphUri($accessToken) . '/v1.0/me/', $accessToken);
        $userData = [
            'user' => $fetched,
        ];
        $uniqueName = $userData['user']['displayName'];
        $uniqueMail = $userData['user']['mail'];
        $givenName = $userData['user']['givenName'];

        $image = NULL;
        try {
            $image = $provider->get($provider->getRootMicrosoftGraphUri($accessToken) . '/v1.0/me/photo/$value', $accessToken);
        }
        catch (IdentityProviderException $e) {
        }

        $base64Image = $image ? base64_encode($image) : null;

        if (!empty($image)) {
            $base64Image = preg_replace('/^data:image\/(png|jpeg);base64,/', '', $base64Image);
            $imageData = base64_decode($base64Image);
            $filename = $uniqueName . '.png';
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/images/profiles/' . $filename;
            file_put_contents($filePath, $imageData);
        }

        $session->set('user', $fetched);
        $error = $authenticationUtils->getLastAuthenticationError();
        $isFullyAuthenticated = $security->isGranted('isFullyAuthenticated');
        $userData = [
            'user' => $fetched,
            'isFullyAuthenticated' => $isFullyAuthenticated,
        ];

        $session->set('userData', $userData);

        // Check if the user already exists in the database
        $userRepository = $entityManager->getRepository(User::class);
        $userId = $fetched['id']; // Assuming the user ID is present in $fetched
        $existingUser = $userRepository->findOneBy(['id' => $userId]);

        if ($existingUser === null) {
            // If the user is not in the database, create a new User entity
            $newUser = new User();
            $newUser->setName($uniqueName);
            $newUser->setId($userId);
            $newUser->setMail($uniqueMail);
            $newUser->setFirstName($givenName);

            $entityManager->persist($newUser);
            $entityManager->flush();

            // Set the newly created user in the session
            $session->set('user', $newUser);
            $session->set('Name', $uniqueName);
        }  else {
            if (empty($existingUser->getFirstName())){
                $existingUser->setFirstName($givenName);
            }
            // User exists, update if necessary
            if ($existingUser->getName() !== $userData['user']['displayName'] || $existingUser->getMail() !== $uniqueMail) {
                // If either name or mail has changed, update and persist
                $existingUser->setName($userData['user']['displayName']);
                $existingUser->setMail($uniqueMail);
            }
            $entityManager->persist($existingUser);
            $entityManager->flush();

            $session->set('user', $existingUser);
            $session->set('Name', $existingUser->getName());
        }

        return $this->redirectToRoute('index',
        );
    }
}