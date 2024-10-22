<?php

namespace App\Controller;

use App\Entity\Locatie;
use App\Entity\User;
use App\Form\ExportType;
use App\Form\LocatieType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ReadController extends AbstractController
{
    public function colleagues(Request $request, mixed $year, mixed $week, EntityManagerInterface $entityManager, SessionInterface $session): Response{

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
            $redirectResponse = $request->headers->get('referer') ?? $this->generateUrl('colleagues', [
                'year' => date('Y'),
                'week' => date('W')
            ], UrlGeneratorInterface::ABSOLUTE_URL); ;
            $response = new RedirectResponse($redirectResponse);
            return $response;
        }
        if (!is_numeric($week) || $currentWeek < 1 || $currentWeek > 52) {
            $error = 'Invalid week. please provide a valid week in the url';
            $this->addFlash('error', $error);
            $redirectResponse = $request->headers->get('referer') ?? $this->generateUrl('colleagues', [
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
            $filename = $username . '.png';
            $filePath = $_SERVER['DOCUMENT_ROOT'] . '/images/profiles/' . $filename;
            if (file_exists($filePath)) {
                $pfp = '/images/profiles/' . $filename;
            }
            else{
                $pfp = '/images/blank-pfp.png';
            }

            $Locations = $this->getUserLocations($year, $week, $username, $entityManager);

            // here we handle the locations that are retrieved from the function
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
            $userDataArray = [];

            foreach ($Locations as $locatie) {
                $username = $locatie->getUsername();

                if (!isset($userDataArray[$username])) {
                    $userDataArray[$username] = [
                        'name' => $username,
                        'profile' => null,
                        'locations' => [], // Initialize locations array
                    ];
                    $filename = $username . '.png';
                    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/images/profiles/' . $filename;
                    if (file_exists($filePath)) {
                        $userDataArray[$username]['profile'] = '/images/profiles/' . $filename;
                    }
                    else{
                        $userDataArray[$username]['profile'] = '/images/blank-pfp.png';
                    }
                }
                $userDataArray[$username]['locations'][] = $locatie->getLocatie_();
            }
            usort($userDataArray, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            foreach ($userDataArray as &$userData) {
                $userData['locations'] = array_map(function ($location) {

                    // Check if the location is '0' and update to 'Kantoor'
                    if ($location === 0) {
                        return 'Kantoor';
                    }
                    // Check if the location is '1' and update to 'Thuis'
                    elseif ($location === 1) {
                        return 'Thuis';
                    }
                    // Check if the location is '2' and update to 'Afwezig'
                    elseif ($location === 2){
                        return 'Afwezig';
                    }
                    // No change for other locations
                    else {
                        return $location;
                    }
                }, $userData['locations']);
            }

            foreach ($userDataArray as $users) {
                $sets = $this->getStandardValuesForDaysByName($entityManager, $users['name']);
                $users['locations'] = array_map(function ($location, $date) use ($sets) {
                    return $location ?? ($sets[$date] ?? null);
                }, $users['locations'], array_keys($sets));
            }

            if (!isset($userFS)){
                $userFS = [];
                $userFS ['name'] = $usernameFromSession;
                $userFS ['profile'] = $pfp;
                $sets = $this->getStandardValuesForDaysByName($entityManager, $username);
                $userFS ['locations'] = $sets;
            }

            $AllUsers = $this->getAllUsers($entityManager);

            // Extract usernames from $AllUsers
            $allUsernames = array_map(function ($user) {
                return $user->getName();
            }, $AllUsers);

            $userDataArrayUsernames = [];

            foreach ($userDataArray as $key => $user){
                $userDataArrayUsernames [] = $user['name'];
            }

            // Finding usernames in $AllUsers that are not in $userDataArrayUsernames
            $usersNotInUserDataArray = array_diff($allUsernames, $userDataArrayUsernames);

            foreach ($usersNotInUserDataArray as $key => $user){
                if ($user === $username){
                    unset($usersNotInUserDataArray[$key]);
                }
                else{
                    $filename = $user. '.png';
                    $filePath = $_SERVER['DOCUMENT_ROOT'] . '/images/profiles/' . $filename;
                    if (file_exists($filePath)) {
                        $pfp = '/images/profiles/' . $filename;
                    }
                    else{
                        $pfp = '/images/blank-pfp.png';
                    }
                    $sets = $this->getStandardValuesForDaysByName($entityManager, $user);
                    $userFS2 = [];
                    $userFS2 ['name'] = $user;
                    $userFS2 ['profile'] = $pfp;
                    $userFS2 ['locations'] = $sets;

                    $userDataArray [] = $userFS2;
                }
            }

            foreach ($userDataArray as $key => $usernames){
                if(strcasecmp($usernames['name'], $usernameFromSession) == 0){
                    $userFS = $usernames;
                    unset($userDataArray[$key]);
                    break;
                }
            }

            $form = $this->initializeForms();

            return $this->render('read/read.html.twig', [
                'error' => $error,
                'currentYear' => $currentYear,
                'currentWeek' => $currentWeek,
                'previousWeek' => $this->getPreviousWeek($currentYear, $currentWeek),
                'nextWeek' => $this->getNextWeek($currentYear, $currentWeek),
                'groupedLocaties' => $groupedLocaties,
                'users' => $userDataArray,
                'loggedUser' => $userFS,
                'pfp' => $pfp,
                'form' => $form,
            ]);
        } else {
            return $this->redirectToRoute('login');
        }
    }

    private function isValidInputDate(mixed $input): bool {
        if (!is_string($input)) {
            return false;
        }

        $dateTime = DateTime::createFromFormat('Y-m-d', $input);

        // Check if the date is a valid string and if it's in the correct format
        if ($dateTime === false || $dateTime->format('Y-m-d') !== $input) {
            return false;
        }

        return true;
    }


    public function exportToExcel(EntityManagerInterface $entityManager, Request $request)
    {

        $errorMsg = 'There is no data found for these dates.';
        $errorMsgInvalid = 'The date you entered is not valid!';
        $startDateInput = $_POST['export']['startDate'] ?? NULL;
        $endDateInput = $_POST['export']['endDate'] ?? NULL;

        $isValidStartDate = $this->isValidInputDate($startDateInput);
        $isValidEndDate = $this->isValidInputDate($endDateInput);

        if ($isValidStartDate = false || $isValidEndDate = false){
            $this->addFlash('error2', $errorMsgInvalid);
            $referer = $request->headers->get('referer');
            $response = new RedirectResponse($referer);
            return $response;
        }

        $data = $this->getUserLocationsForExport($entityManager, $startDateInput, $endDateInput) ?? NULL;
        $foundInvalidLocation = false;

        if (!isset($data)) {
            $this->addFlash('error', $errorMsg);
            $referer = $request->headers->get('referer');
            $response = new RedirectResponse($referer);
            return $response;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $locationLabels = [
            NULL => 'NULL',
            0    => 'Kantoor',
            1    => 'Thuis',
            2    => 'Afwezig',
        ];

        // Add header row
        $sheet->setCellValue('A1', 'Username');
        $sheet->setCellValue('B1', 'Location');
        $sheet->setCellValue('C1', 'Date');

        // Fill data
        $row = 2;
        foreach ($data as $user) {
            foreach ($user as $locatie){
                if (is_object($locatie)) {
                    $sheet->setCellValue('A' . $row, $locatie->getUsername());

                    // Map location ID to its label
                    $locationLabel = $locationLabels[$locatie->getLocatie_()];
                    $sheet->setCellValue('B' . $row, $locationLabel);
                    $sheet->setCellValue('C' . $row, $locatie->getDate());
                    $row++;

                } else {
                    if ($locatie['location'] === 0) {
                        $locatie['location'] = 'Kantoor';
                    } elseif ($locatie['location'] === 1) {
                        $locatie['location'] = 'Thuis';
                    } elseif ($locatie['location'] === 2) {
                        $locatie['location'] = 'Afwezig';
                    }
                    else{
                        $locatie['location'] = NULL;
                    }
                    $sheet->setCellValue('A' . $row, $locatie['name']);

                    // Map location ID to its label
                    $locationLabel = $locatie['location'];
                    $sheet->setCellValue('B' . $row, $locationLabel);
                    $sheet->setCellValue('C' . $row, $locatie['date']);
                    $row++;
                }
            }
        }

        // Save the spreadsheet to a temporary file
        $tempFilePath = tempnam(sys_get_temp_dir(), 'export_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);

        // Create a response with the file
        $response = new Response(file_get_contents($tempFilePath));
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="kantoorplanning.xlsx"');
        $response->headers->set('Cache-Control', 'max-age=0');

        // Clean up temporary file
        unlink($tempFilePath);

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

    private function generateDateRange($start_date, $end_date) {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);

        $date_range = array();

        while ($start <= $end) {
            if ($start->format('N') < 6) {
                $date_range[] = $start->format('Y-m-d');
            }

            $start->modify('+1 day');
        }

        return $date_range;
    }

    private function getUserLocationsForExport(EntityManagerInterface $entityManager, $startDate, $endDate): array
    {
        $locatieRepository = $entityManager->getRepository(Locatie::class);

        $qb = $locatieRepository->createQueryBuilder('l');
        $users = $this->getAllUsers($entityManager);
        foreach ($users as $user) {
            $date_array = $this->generateDateRange($startDate, $endDate);

            foreach ($date_array as $date) {
                $result = $qb
                    ->andWhere('l.username = :user')
                    ->andWhere('l.date = :date')
                    ->setParameter('user', $user->getName())
                    ->setParameter('date', $date)
                    ->getQuery();

                $resultObject = $result->getOneOrNullResult();

                // Check if $resultObject is null and create a new Locatie object with the date
                if ($resultObject === null) {

                    $userLocations[$user->getName()][] = $date;
                } else {
                    $userLocations[$user->getName()][] = $resultObject;
                }
            }
        }

        foreach ($userLocations as $keyName => $userLocation) {
            foreach ($userLocation as $key => $location) {
                if (is_object($location)) {
                    if ($location->getLocatie_() === NULL) {
                        $location->setLocatie_($this->getStandardValuesForDaysByNameForExportLocationOnly($entityManager, $location->getUsername(), $location->getDate()));
                    } else {
                        continue;
                    }
                }
                if (!is_object($location)) {
                    $standardValue = $this->getStandardValuesForDaysByNameForExport($entityManager, $keyName, $userLocation[$key]);
                    unset($userLocations[$keyName][$key]);
                    $userLocations[$keyName][] = $standardValue;
                }
            }
        }

        return $userLocations;
    }

    private function getDatesRange($startDate, $endDate, $existingDates): array
    {
        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, $end);

        $existingDates = array_map(function ($date) {
            return $date['date'];
        }, $existingDates);

        $missingDates = [];

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            if (!in_array($formattedDate, $existingDates)) {
                $missingDates[] = $formattedDate;
            }
        }

        return $missingDates;
    }

    private function getUserLocations(int $year, int $week, string $username, EntityManagerInterface $entityManager): array
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

    private function initializeForms(): mixed
    {
        $form = $this->createForm(ExportType::class, null, [
            'action' => $this->generateUrl('export_to_excel', [
            ]),
        ]);
        return $form;
    }
    private function getAllUsers(EntityManagerInterface $entityManager): array
    {
        $locatieRepository = $entityManager->getRepository(User::class);
            $qb = $locatieRepository->createQueryBuilder('l');
            $query = $qb
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

        // Update the values based on conditions
        $standardValues = [
            '0' => $result['monday'],
            '1' => $result['tuesday'],
            '2' => $result['wednesday'],
            '3' => $result['thursday'],
            '4' => $result['friday'],
        ];

        foreach ($standardValues as $day => &$value) {
            // Example checks, modify as per your requirements
            if ($value === 0) {
                $value = 'Kantoor';
            } elseif ($value === 1) {
                $value = 'Thuis';
            } elseif ($value === 2) {
                $value = 'Afwezig';
            }
            else{
                $value = NULL;
            }
        }

        return $standardValues;
    }
    private function getStandardValuesForDaysByNameForExport(EntityManagerInterface $entityManager, string $name, string $day): ?array
    {
        $dateD = $day;

        $dateTime = DateTime::createFromFormat('Y-m-d', $day);

        $day = $dateTime->format('l');

        if (ctype_upper($day[0])) {
            // Convert the first letter to lowercase
            $day = strtolower($day);
        }

        $queryBuilder = $entityManager->createQueryBuilder();

        $query = $queryBuilder
            ->select("u.$day")  // Select the specific day from the User entity
            ->from(User::class, 'u')
            ->where('u.name = :name')
            ->setParameter('name', $name)
            ->getQuery();

        $result = $query->getOneOrNullResult();

        $user = [];

        $user['name'] = $name;
        $user['date'] = $dateD;
        $user['location'] = $result[$day];

        return $user;
    }
    private function getStandardValuesForDaysByNameForExportLocationOnly(EntityManagerInterface $entityManager, string $name, string $day): ?int
    {

        $dateTime = DateTime::createFromFormat('Y-m-d', $day);

        $day = $dateTime->format('l');

        if (ctype_upper($day[0])) {
            // Convert the first letter to lowercase
            $day = strtolower($day);
        }

        $queryBuilder = $entityManager->createQueryBuilder();

        $query = $queryBuilder
            ->select("u.$day")  // Select the specific day from the User entity
            ->from(User::class, 'u')
            ->where('u.name = :name')
            ->setParameter('name', $name)
            ->getQuery();

        $result = $query->getOneOrNullResult();

        foreach($result as $test) {
            $result = $test;
        }


        return $result;
    }

}
