<?php

declare(strict_types=1);

namespace AndyDefer\Actions\Contracts;

use AndyDefer\DomainStructures\Abstracts\AbstractData;

/**
 * Interface for objects that can export their data to a Data object.
 *
 * This interface provides a contract for transforming objects into a structured
 * Data object representation. It is particularly useful for:
 * - Eloquent models
 * - Value Objects
 * - Collections
 * - Any domain object that needs to be exported as structured data
 *
 * @template T of AbstractData
 *
 * @author Andy Defer
 *
 * @version 1.0.0
 *
 * @example
 * ```php
 * class Doctor extends Model implements ExportableInterface
 * {
 *     public function toData(): AbstractData
 *     {
 *         return DoctorData::from([
 *             'id' => $this->id,
 *             'name' => $this->name,
 *             'email' => $this->email,
 *         ]);
 *     }
 * }
 *
 * $doctorData = $doctor->toData();
 * ```
 */
interface ExportableInterface
{
    /**
     * Export the current object's data to a Data object.
     *
     * This method should transform the current object into a structured
     * Data object that represents its state. The returned Data object
     * should be immutable and contain all relevant properties.
     *
     * @return AbstractData The Data object representing the current state
     *
     * @throws \RuntimeException If the data cannot be exported
     * @throws \InvalidArgumentException If the data structure is invalid
     *
     * @example
     * ```php
     * $data = $user->toData();
     * echo $data->name; // Access properties as object
     * ```
     */
    public function toData(): AbstractData;
}
