<?php

namespace App\Entity;

use App\Repository\OSRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OSRepository::class)]
class OS
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * @var Collection<int, Logic>
     */
    #[ORM\ManyToMany(targetEntity: Logic::class, mappedBy: 'OS')]
    private Collection $logics;

    /**
     * @var Collection<int, Script>
     */
    #[ORM\ManyToMany(targetEntity: Script::class, mappedBy: 'OS')]
    private Collection $scripts;

    public function __construct()
    {
        $this->logics = new ArrayCollection();
        $this->scripts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, Logic>
     */
    public function getLogics(): Collection
    {
        return $this->logics;
    }

    public function addLogic(Logic $logic): static
    {
        if (!$this->logics->contains($logic)) {
            $this->logics->add($logic);
            $logic->addO($this);
        }

        return $this;
    }

    public function removeLogic(Logic $logic): static
    {
        if ($this->logics->removeElement($logic)) {
            $logic->removeO($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Script>
     */
    public function getScripts(): Collection
    {
        return $this->scripts;
    }

    public function addScript(Script $script): static
    {
        if (!$this->scripts->contains($script)) {
            $this->scripts->add($script);
            $script->addO($this);
        }

        return $this;
    }

    public function removeScript(Script $script): static
    {
        if ($this->scripts->removeElement($script)) {
            $script->removeO($this);
        }

        return $this;
    }
}
