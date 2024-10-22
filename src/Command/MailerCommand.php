<?php

namespace App\Command;

use App\Entity\Locatie;
use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Doctrine\ORM\EntityManagerInterface;


class MailerCommand extends Command
{
    private $mailer;

    public function __construct(MailerInterface $mailer, EntityManagerInterface $manager)
    {
        parent::__construct();
        $this->manager = $manager;
        $this->mailer = $mailer;    }

    protected function configure()
    {
        $this->setName('app:send-email')
            ->setDescription('Send a test email using Symfony Mailer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentDate = new \DateTime();
        $currentWeek = $currentDate->format('W');
        $currentYear = $currentDate->format('Y');

        $usersMissingDay = [];

        $entities = $this->manager->getRepository(User::class)->findAll();

        foreach ($entities as $entity) {
            for ($day = 0; $day <= 4; $day++) {
                $columnName = strtolower(date('l', strtotime("Monday +{$day} days")));
                $getterMethod = 'get' . ucfirst($columnName);
                if ($entity->$getterMethod() === null) {
                    $usersMissingDay[] = $entity;
                    break;
                }
            }
        }
        $users = [];
        foreach ($usersMissingDay as $user) {
            $userLocation = $this->getUserLocations($currentYear, $currentWeek, $user->getName(), $this->manager);
            if (empty($userLocation)){
                $users[] = $user;
            }
        }

        $io = new SymfonyStyle($input, $output);

        foreach ($users as $user) {
            if (empty($user->getMail())) {
                continue;
            } else {
                if (empty($userName = $user->getFirstName())) {
                    $userName = '';
                } else {
                    $userName = $user->getFirstName();
                }
                $email = (new TemplatedEmail())
                    ->from(new Address('noreply@develto.nl'))
                    ->to($user->getMail())
                    ->subject('Where is he? No one knows...')
                    ->htmlTemplate('email.template.html.twig')
                    ->context([
                        'userName' => $userName,
                    ]);

                $email->getHeaders()
                    ->addTextHeader('X-Mailer', 'PHP/' . phpversion());

                $this->mailer->send($email);
            }
        }

        $io->success('Email sent successfully!');


        return Command::SUCCESS;
    }
    private function getUserLocations(int $year, int $week, string $username, EntityManagerInterface $entityManager): array
    {
        $locatieRepository = $entityManager->getRepository(Locatie::class);

        $qb = $locatieRepository->createQueryBuilder('l');
        $query = $qb
            ->where('l.date >= :start_date')
            ->andWhere('l.date <= :end_date')
            ->andWhere('l.username = :username')
            ->setParameter('start_date', (new \DateTime())->setISODate($year, $week, 0))
            ->setParameter('end_date', (new \DateTime())->setISODate($year, $week, 5))
            ->setParameter('username', $username)
            ->getQuery();

        return $query->getResult();
    }
}
