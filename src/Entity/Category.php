<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    private ?int $parent_id = null;

    /**
     * @var Collection<int, Logic>
     */
    #[ORM\OneToMany(targetEntity: Logic::class, mappedBy: 'category')]
    private Collection $logics;

    /**
     * @var Collection<int, Script>
     */
    #[ORM\OneToMany(targetEntity: Script::class, mappedBy: 'category')]
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function setParentId(?int $parent_id): static
    {
        $this->parent_id = $parent_id;

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
            $logic->setCategory($this);
        }

        return $this;
    }

    public function removeLogic(Logic $logic): static
    {
        if ($this->logics->removeElement($logic)) {
            // set the owning side to null (unless already changed)
            if ($logic->getCategory() === $this) {
                $logic->setCategory(null);
            }
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
            $script->setCategory($this);
        }

        return $this;
    }

    public function removeScript(Script $script): static
    {
        if ($this->scripts->removeElement($script)) {
            // set the owning side to null (unless already changed)
            if ($script->getCategory() === $this) {
                $script->setCategory(null);
            }
        }

        return $this;
    }
}
