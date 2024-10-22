<?php

namespace App\Controller;

use App\Entity\Locatie;
use App\Entity\User;
use App\Form\LocatieType;
use App\Form\defaultValueType;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LocatieController extends AbstractController
{

    public function mijnLocatie(Request $request, mixed $year, mixed $week, EntityManagerInterface $entityManager, SessionInterface $session): Response
    {
        $error = FALSE;
        // If $year or $week is not provided in the URL, set them to the current year and week.
        if ($year === null || $week === null) {
            $currentYear = (int)date('Y');
            $currentWeek = (int)date('W');
        } else {
            // Check if $year and $week are valid integers, and handle errors if needed.
            $currentYear = (int)$year;
            $currentWeek = (int)$week;

            if (!is_numeric($year) || $currentYear < 1000 || $currentYear > 9999) {
                $error = 'Invalid year. please provide a valid year in the url.';
                $this->addFlash('error', $error);
                $redirectResponse = $request->headers->get('referer') ?? $this->generateUrl('locatie', [
                    'year' => date('Y'),
                    'week' => date('W')
                ], UrlGeneratorInterface::ABSOLUTE_URL); ;
                $response = new RedirectResponse($redirectResponse);
                return $response;
            }
            if (!is_numeric($week) || $currentWeek < 1 || $currentWeek > 52) {
                $error = 'Invalid week. please provide a valid week in the url';
                $this->addFlash('error', $error);
                $redirectResponse = $request->headers->get('referer') ?? $this->generateUrl('locatie', [
                    'year' => date('Y'),
                    'week' => date('W')
                ], UrlGeneratorInterface::ABSOLUTE_URL); ;
                $response = new RedirectResponse($redirectResponse);
                return $response;
            }
        }

        if ($userData = $session->get('userData') !== null) {
            $userData = $session->get('userData');
            $username = $userData['user']['displayName'] ?? null;

            $Locations = $this->getUserLocations($year, $week, $username, $entityManager);
            $locatieEntity = new Locatie();

            $locationAll = array();

            foreach ($Locations as $LocationUser) {
                $locationFS = $LocationUser->getLocatie_() ?? NULL;
                $locationAll[$LocationUser->getdate()] = $locationFS ?? NULL;
            }
            if (empty($locationAll)) {
                $locationAll = [
                    "monday" => NULL,
                    "tuesday" => NULL,
                    "wednesday" => NULL,
                    "thursday" => NULL,
                    "friday" => NULL,
                    ];

            }
            $Locations = array_map(function ($locatie) {
                $date = new \DateTime($locatie->getDate());
                $locatie->setDate($date->format('l'));
                return $locatie;
            }, $Locations);

            $groupedLocaties = [];
            foreach ($Locations as $locatie) {
                $usernameFromSession = $locatie->getUsername();
                $groupedLocaties[$username][] = $locatie;
            }

            $usernameFromSession = $userData['user']['displayName'] ?? null;


            $groupedLocaties = [];
            $weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            $sets = $this->getStandardValuesForDaysByName($entityManager, $username);

            // Use array_map to replace NULL values in $locationAll with values from $sets
            $locationAll = array_map(function ($location, $date) use ($sets) {
                return $location ?? ($sets[$date] ?? null);
            }, $locationAll, array_keys($sets));

            $locationAll = array_combine($weekDays, $locationAll);
            $form = $this->initializeForms($currentYear, $currentWeek, $entityManager, $request, $locationAll, $Locations);
            $formDefaultValue = $this->initializeForms2($entityManager, $sets);
            $allUserLocations = $this->getAllUserLocations($year, $week, $username, $entityManager);

            $allUserLocations = array_map(function ($locatie) {
                $date = new \DateTime($locatie->getDate());
                $locatie->setDate($date->format('l'));
                return $locatie;
            }, $allUserLocations);
            // Calculate the start date of the given week
            $startDate = new DateTime();
            $startDate->setISODate($currentYear, $currentWeek);
            // Initialize an array to store the dates for each day of the week
            $weekDates = [];

            // Loop through the days of the week and store the dates
            for ($i = 0; $i <= 4; $i++) {  // Assuming a standard workweek (Monday to Friday)
                $currentDate = clone $startDate;
                $currentDate->add(new DateInterval("P{$i}D"));  // Incrementing the day

                $weekDates[] = $currentDate->format('Y-m-d');
            }
            return $this->render('mijnLocatie/mijnLocatie.html.twig', [
                'error' => $error,
                'currentYear' => $currentYear,
                'currentWeek' => $currentWeek,
                'previousWeek' => $this->getPreviousWeek($currentYear, $currentWeek),
                'nextWeek' => $this->getNextWeek($currentYear, $currentWeek),
                'form' => $form,
                'formDefaultValue' => $formDefaultValue,
                'weekDays' => $weekDays,
                'allUserLocations' => $allUserLocations,
                'groupedLocaties' => $groupedLocaties,
                'weekDates' => $weekDates,
            ]);

        } else {
            return $this->redirectToRoute('login');
        }
    }
    private function initializeForms(int $currentYear, int $currentWeek, EntityManagerInterface $entityManager, Request $request, $locationAll, $weekDates): mixed
    {
        $form = $this->createForm(LocatieType::class, null, [
            'action' => $this->generateUrl('handleFormSubmissionLocatie', [
                'currentYear' => $currentYear,
                'currentWeek' => $currentWeek,
                'weekDates' => $weekDates,
                'dataLocs' => $locationAll,
            ]),
            'weekDates' => $weekDates,
            'locationAll' => $locationAll,

        ]);
        return $form;
    }
    private function initializeForms2(EntityManagerInterface $entityManager, $locationAll): mixed
    {
        $form = $this->createForm(defaultValueType::class, null, [
            'action' => $this->generateUrl('handleFormSubmissionDefaultLocatie', [
                'dataLocs' => $locationAll,
            ]),
            'locationAll' => $locationAll,
        ]);
        return $form;
    }

    public function handleFormSubmissionLocatie(Request $request, EntityManagerInterface $entityManager, SessionInterface $session, $currentYear, $currentWeek): Response
    {
        $date = new DateTime();
        $date->setISODate($currentYear, $currentWeek, 1);

        $datesArray = [];

        for ($i = 0; $i < 5; $i++) {
            $datesArray[] = $date->format('Y-m-d');
            $date->modify('+1 day');
        }

        $postLocation = [
            'Monday' => $_POST['locatie']['Monday'] ?? NULL,
            'Tuesday' => $_POST['locatie']['Tuesday'] ?? NULL,
            'Wednesday' => $_POST['locatie']['Wednesday'] ?? NULL,
            'Thursday' => $_POST['locatie']['Thursday'] ?? NULL,
            'Friday' => $_POST['locatie']['Friday'] ?? NULL,
        ];

        $errorMsg1 = 'Careful! That\'s not allowed!';
        $errorMsg2 = 'Please fill in at least one day before submitting!';
        $errorMsg3 = 'Please change at least one of the items before submitting!';
        $successMessage = 'your location has been submitted!';
        $allowedLocatieOptions = [0, 1, 2, ''];
        $foundInvalidLocation = false;

        foreach ($postLocation as $location) {
            if (!in_array($location, $allowedLocatieOptions)) {
                $foundInvalidLocation = true;
                $this->addFlash('error', $errorMsg1);
                break;
            }
        }
        $allEmpty = true;

        foreach ($postLocation as $location) {
            if ($location !== NULL) {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            $this->addFlash('error2',  $errorMsg2);
            $referer = $request->headers->get('referer');
            $response = new RedirectResponse($referer);
            return $response;
        }
        if ($foundInvalidLocation) {
            $this->addFlash('error',  $errorMsg1);
            $referer = $request->headers->get('referer');
            $response = new RedirectResponse($referer);
            return $response;
        }
        $locatieEntity = new Locatie();

        $sessionData = $session->get('userData');
        $name = $sessionData['user']['displayName'] ?? null;
        $locatieEntities = [];
        $repository = $entityManager->getRepository(Locatie::class);

        foreach ($datesArray as $day) {
            $object = $repository->createQueryBuilder('l')
                ->andWhere('l.username = :username')
                ->andWhere('l.date = :date')
                ->setParameter('username', $name)
                ->setParameter('date', $day)
                ->getQuery()
                ->getOneOrNullResult();

            // Check if $object is not null before attempting to get the location
            if ($object) {
                $locatieEntities[] = $object->getLocatie_();
            }
            else {
                $locatieEntities [] = $object;
            }
        }
        if (array_values($postLocation) == array_values($locatieEntities)) {
            $this->addFlash('error3', $errorMsg3);
            $referer = $request->headers->get('referer');
            $response = new RedirectResponse($referer);
            return $response;
        }
        $weekDays = range(0, 4);

        $locationCount = count($postLocation);
        $result = array_map(function ($day, $location) use ($currentYear, $currentWeek, $name, $entityManager, $repository) {
            $locatieEntity = new Locatie();
            $locatieEntity->setDatumByWeekAndDay($currentYear, $currentWeek, $day);

            // Set location only if it is not empty
            if ($location !== NULL) {
                $locatieEntity->setLocatie_($location);
            }

            $locatieEntity->setUsername($name);

            // Use the QueryBuilder to check if an object with the same username and date exists
            $existingLocatieEntity = $repository->createQueryBuilder('l')
                ->andWhere('l.username = :username')
                ->andWhere('l.date = :date')
                ->setParameter('username', $name)
                ->setParameter('date', $locatieEntity->getDate())
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingLocatieEntity) {
                // If an existing entity is found, delete it
                $entityManager->remove($existingLocatieEntity);
            }

            $entityManager->persist($locatieEntity);
            $entityManager->flush();
        }, $weekDays, $postLocation);


        $this->addFlash('success', $successMessage);
        $referer = $request->headers->get('referer');
        $response = new RedirectResponse($referer);
        return $response;
    }

    public function handleFormSubmissionDefaultLocatie(Request $request, EntityManagerInterface $entityManager, SessionInterface $session ){
        $postLocation = [
            'Monday' => $_POST['default_value']['Monday'] ?? NULL,
            'Tuesday' => $_POST['default_value']['Tuesday'] ?? NULL,
            'Wednesday' => $_POST['default_value']['Wednesday'] ?? NULL,
            'Thursday' => $_POST['default_value']['Thursday'] ?? NULL,
            'Friday' => $_POST['default_value']['Friday'] ?? NULL,
        ];

        $errorMsg1 = 'Careful! That\'s not allowed.';
        $errorMsg2 = 'Please fill in at least one day before submitting!';
        $errorMsg3 = 'Please change at least one of the items before submitting!';
        $successMessage = 'your standard value has been set, the set days will be filled in for you';
        $allowedLocatieOptions = [0, 1, 2, ''];
        $foundInvalidLocation = false;

        foreach ($postLocation as $location) {
            if (!in_array($location, $allowedLocatieOptions)) {
                $foundInvalidLocation = true;
                $this->addFlash('error', $errorMsg1);
                break;
            }
        }
        $allEmpty = true;

        foreach ($postLocation as $location) {
            if ($location !== NULL) {
                $allEmpty = false;
                break;
            }
        }
        if ($allEmpty) {
            $this->addFlash('error2',  $errorMsg2);
            $referer = $request->headers->get('referer');
            $response = new RedirectResponse($referer);
            return $response;
        }
        if ($foundInvalidLocation) {
            $this->addFlash('error',  $errorMsg1);
            $referer = $request->headers->get('referer');
            $response = new RedirectResponse($referer);
            return $response;
        }
        $locatieEntity = new User();

        $sessionData = $session->get('userData');
        $name = $sessionData['user']['displayName'] ?? null;

        // Retrieve the User entity for the current user
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['name' => $name]);

        if (!$user) {
            // Handle the case where the user entity is not found (create new user or show an error)
            // For example, you can create a new user with the provided name
            $user = new User();
            $user->setName($name);
            $entityManager->persist($user);
        }

        // Set the standard values for each day
        $user->setMonday($postLocation['Monday']);
        $user->setTuesday($postLocation['Tuesday']);
        $user->setWednesday($postLocation['Wednesday']);
        $user->setThursday($postLocation['Thursday']);
        $user->setFriday($postLocation['Friday']);

        // Optionally, you can set Saturday and Sunday if needed

        // Flush the changes to the database
        $entityManager->flush();

        $this->addFlash('success', $successMessage);
        $referer = $request->headers->get('referer');
        $response = new RedirectResponse($referer);
        return $response;
    }


    private function getPreviousWeek(int $currentYear = null, int $currentWeek = null): array
    {
        $currentWeekStartDate = new \DateTime();
        $currentWeekStartDate->setISODate($currentYear, $currentWeek);
        $currentWeekStartDate->modify('-1 week');

        return [
            (int)$currentWeekStartDate->format('Y'),
            (int)$currentWeekStartDate->format('W'),
        ];
    }

    private function getNextWeek(int $currentYear = null, int $currentWeek = null): array
    {
        $currentWeekStartDate = new \DateTime();
        $currentWeekStartDate->setISODate($currentYear, $currentWeek);
        $currentWeekStartDate->modify('+1 week');

        return [
            (int)$currentWeekStartDate->format('Y'),
            (int)$currentWeekStartDate->format('W'),
        ];
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

    private function getAllUserLocations(int $year, int $week, string $username, EntityManagerInterface $entityManager): array
    {
        $locatieRepository = $entityManager->getRepository(Locatie::class);

        $qb = $locatieRepository->createQueryBuilder('l');
        $query = $qb
            ->where('l.date >= :start_date')
            ->andWhere('l.date <= :end_date')
            ->setParameter('start_date', (new \DateTime())->setISODate($year, $week, 0))
            ->setParameter('end_date', (new \DateTime())->setISODate($year, $week, 5))
            ->getQuery();

        return $query->getResult();
    }
    private function getStandardValuesForDaysByName(EntityManagerInterface $entityManager, string $name): array
    {
        $queryBuilder = $entityManager->createQueryBuilder();

        $query = $queryBuilder
            ->select('u.monday', 'u.tuesday', 'u.wednesday', 'u.thursday', 'u.friday')
            ->from(User::class, 'u')
            ->where('u.name = :name')
            ->setParameter('name', $name)
            ->getQuery();

        $result = $query->getOneOrNullResult();

        if (!$result) {
            return [];
        }

        return [
            'Monday' => $result['monday'],
            'Tuesday' => $result['tuesday'],
            'Wednesday' => $result['wednesday'],
            'Thursday' => $result['thursday'],
            'Friday' => $result['friday'],
        ];
    }
}

