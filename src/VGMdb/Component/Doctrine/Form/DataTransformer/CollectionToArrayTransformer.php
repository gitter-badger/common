<?php

/*
 * This file was originally part of the Symfony DoctrineBridge.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace VGMdb\Component\Doctrine\Form\DataTransformer;

use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CollectionToArrayTransformer implements DataTransformerInterface
{
    /**
     * Transforms a collection into an array.
     *
     * @param Collection $collection A collection of entities
     *
     * @return mixed An array of entities
     *
     * @throws TransformationFailedException
     */
    public function transform($collection)
    {
        if (null === $collection) {
            return array();
        }

        if (!$collection instanceof Collection) {
            throw new TransformationFailedException('Expected a Doctrine\Common\Collections\Collection object.');
        }

        return $collection->toArray();
    }

    /**
     * Transforms choice keys into entities.
     *
     * @param mixed $array An array of entities
     *
     * @return Collection   A collection of entities
     */
    public function reverseTransform($array)
    {
        if ('' === $array || null === $array) {
            $array = array();
        } else {
            $array = (array) $array;
        }

        return new ArrayCollection($array);
    }
}
