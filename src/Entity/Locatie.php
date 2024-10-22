<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\LocatieRepository")
 */
class Locatie
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private ?int $id = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $locatie_ = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $username = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLocatie_(): ?int
    {
        return $this->locatie_;
    }

    public function setLocatie_(?int $locatie_): self
    {
        $this->locatie_ = $locatie_;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Set the date based on the year, month, and day
     *
     * @param int $year
     * @param int $week
     * @param string $dayOfWeek
     * @return self
     */
    public function setDatumByWeekAndDay(int $year, int $week, string $dayOfWeek): self
    {
        $startOfWeek = new \DateTime();
        $startOfWeek->setISODate($year, $week);

        // Calculate the date for the submitted day of the week
        $date = clone $startOfWeek;
        $date->modify("$dayOfWeek days");

        // Set the time component to midnight
        $date->setTime(0, 0, 0);

        // Format the date as "Y-m-d" and set it to $this->date
        $this->date = $date->format('Y-m-d');

        return $this;
    }
}
