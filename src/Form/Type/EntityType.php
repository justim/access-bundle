<?php

declare(strict_types=1);

namespace Access\AccessBundle\Form\Type;

use Access\AccessBundle\Form\ChoiceList\EntityChoiceLoader;
use Access\Database;
use Access\Entity;
use Access\Query\Select;
use Override;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\ChoiceList\Factory\Cache\ChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @template-extends AbstractType<Entity>
 */
final class EntityType extends AbstractType
{
    public function __construct(private readonly Database $db) {}

    #[Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void {}

    #[Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $choiceLoader = function (Options $options): ChoiceLoader {
            /** @var class-string<Entity> $entityClass */
            $entityClass = $options['class'];

            /** @var Select|null $choiceQuery */
            $choiceQuery = $options['choice_query'];

            return ChoiceList::loader(
                $this,
                new EntityChoiceLoader($this->db, $entityClass, $choiceQuery),
                [$entityClass, $choiceQuery],
            );
        };

        $resolver->setDefaults([
            'class' => null,
            'choice_loader' => $choiceLoader,
            'choice_label' => ChoiceList::label($this, $this->createChoiceLabel(...)),
            'choice_value' => ChoiceList::value($this, $this->createChoiceValue(...)),
            'choice_query' => null,
        ]);

        $resolver->setAllowedTypes('class', 'string');
        $resolver->setAllowedTypes('choice_query', ['null', Select::class]);

        $resolver->setRequired(['class']);
    }

    private function createChoiceLabel(Entity $entity): string
    {
        return (string) $entity->getId();
    }

    private function createChoiceValue(Entity|int|null $entity): ?int
    {
        if (is_int($entity)) {
            return $entity;
        }

        return $entity?->getId();
    }

    #[Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
