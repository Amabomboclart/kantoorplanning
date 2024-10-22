<?php

namespace App\Controller;

use App\Entity\Locatie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
class HomeController extends AbstractController
{
    public function index(SessionInterface $session, EntityManagerInterface $entityManager): Response
    {
        if ($userData = $session->get('userData') !== null) {
            $userData = $session->get('userData');
            $username = $userData['user']['displayName'] ?? null;
            $year = (int) date('Y');
            $week = (int) date('W');

            $todaysLocations = $this->getUserLocationsForCurrentDay($username, $entityManager,);

            foreach ($todaysLocations as &$locatie) {
                if ($locatie['username'] === $username) {
                    $locatie['username'] = 'You';
                }
            }
            return $this->redirectToRoute('locatie', [
                'week' => $week,
                'year' => $year,
            ]);
        } else {
            return $this->redirectToRoute('login');
        }
    }
    public function logout(SessionInterface $session): RedirectResponse
    {
        $session->invalidate();
        return $this->redirectToRoute('login');
    }

    private function getUserLocationsForCurrentDay(string $username, EntityManagerInterface $entityManager): array
    {
        $locatieRepository = $entityManager->getRepository(Locatie::class);

        $currentDate = new \DateTime();
        $currentDate->setTime(0, 0, 0);

        $qb = $locatieRepository->createQueryBuilder('l');
        $query = $qb
            ->select('l')
            ->where('l.date >= :start_date')
            ->andWhere('l.date <= :end_date')
            ->andWhere('l.locatie_ = :locatie_')
            ->setParameter('start_date', $currentDate)
            ->setParameter('end_date', $currentDate)
            ->setParameter('locatie_', 'Op kantoor')
            ->getQuery();

        return $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
    }

    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function sendWeeklyEmail()
    {
        // Calculate the next Monday morning at 8:30 European time
        $nextMonday = new \DateTime('next monday 08:30', new \DateTimeZone('Europe/Berlin'));

        // Check if today is Monday
        if (date('N') === 1) {
            // If today is Monday, send the email
            $email = (new Email())
                ->from('your@email.com')
                ->to('user@email.com')
                ->subject('Weekly Reminder')
                ->text('This is your weekly reminder email.');

            $this->mailer->send($email);
        }

        // Schedule the next execution
        $nextExecution = $nextMonday->format('Y-m-d H:i:s');
        file_put_contents('/path/to/next_execution.txt', $nextExecution);
    }

}
