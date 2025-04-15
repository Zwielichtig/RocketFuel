<?php

namespace App\Entity;

use App\Repository\LogicTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LogicTypeRepository::class)]
class LogicType
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
    #[ORM\OneToMany(targetEntity: Logic::class, mappedBy: 'type', orphanRemoval: true)]
    private Collection $logics;

    public function __construct()
    {
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
            $logic->setType($this);
        }

        return $this;
    }

    public function removeLogic(Logic $logic): static
    {
        if ($this->logics->removeElement($logic)) {
            // set the owning side to null (unless already changed)
            if ($logic->getType() === $this) {
                $logic->setType(null);
            }
        }

        return $this;
    }
}
