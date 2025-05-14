<?php

namespace App\Entity;

use App\Repository\PackageManagerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PackageManagerRepository::class)]
class PackageManager
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    /**
     * @var Collection<int, Script>
     */
    #[ORM\OneToMany(targetEntity: Script::class, mappedBy: 'package_manager')]
    private Collection $scripts;

    /**
     * @var Collection<int, Logic>
     */
    #[ORM\OneToMany(targetEntity: Logic::class, mappedBy: 'package_manager')]
    private Collection $logics;

    public function __construct()
    {
        $this->scripts = new ArrayCollection();
        $this->logics = new ArrayCollection();
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
            $script->setPackageManager($this);
        }

        return $this;
    }

    public function removeScript(Script $script): static
    {
        if ($this->scripts->removeElement($script)) {
            // set the owning side to null (unless already changed)
            if ($script->getPackageManager() === $this) {
                $script->setPackageManager(null);
            }
        }

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
            $logic->setPackageManager($this);
        }

        return $this;
    }

    public function removeLogic(Logic $logic): static
    {
        if ($this->logics->removeElement($logic)) {
            // set the owning side to null (unless already changed)
            if ($logic->getPackageManager() === $this) {
                $logic->setPackageManager(null);
            }
        }

        return $this;
    }
}
