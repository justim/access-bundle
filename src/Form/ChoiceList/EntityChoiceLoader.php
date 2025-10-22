<?php

declare(strict_types=1);

namespace Access\AccessBundle\Form\ChoiceList;

use Access\Database;
use Access\Entity;
use Access\Query\Select;
use Override;
use Symfony\Component\Form\ChoiceList\Loader\AbstractChoiceLoader;

final class EntityChoiceLoader extends AbstractChoiceLoader
{
    /**
     * @param class-string<Entity> $entityClass
     */
    public function __construct(
        private readonly Database $db,
        private readonly string $entityClass,
        private readonly ?Select $choiceQuery = null,
    ) {}

    #[Override]
    protected function loadChoices(): iterable
    {
        $repo = $this->db->getRepository($this->entityClass);

        $query = $this->choiceQuery ?? new Select($this->entityClass);

        return $repo->selectCollection($query);
    }
}
