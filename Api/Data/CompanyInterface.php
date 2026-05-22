<?php

/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Api\Data;

interface CompanyInterface
{
    public const ENTITY_ID = 'entity_id';
    public const NAME = 'name';
    public const EMAIL = 'email';
    public const TAX_ID = 'tax_id';
    public const NAME_LEGAL = 'name_legal';
    public const ADDRESS = 'address';
    public const CITY = 'city';
    public const COUNTRY = 'country';
    public const REGION = 'region';
    public const POSTALCODE = 'postalcode';
    public const TELEPHONE = 'telephone';
    public const STATUS = 'status';
    public const WEBSITE_ID = 'website_id';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get Email
     *
     * @return string|null
     */
    public function getEmail();

    /**
     * Set Email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email);

    /**
     * Get Status
     *
     * @return int|null
     */
    public function getStatus();

    /**
     * Set Status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Get Legal Name
     *
     * @return string|null
     */
    public function getNameLegal();

    /**
     * Set Legal Name
     *
     * @param string $nameLegal
     * @return $this
     */
    public function setNameLegal($nameLegal);

    /**
     * Get Address
     *
     * @return string|null
     */
    public function getAddress();

    /**
     * Set Address
     *
     * @param string $address
     * @return $this
     */
    public function setAddress($address);

    /**
     * Get City
     *
     * @return string|null
     */
    public function getCity();

    /**
     * Set City
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city);

    /**
     * Get Country
     *
     * @return string|null
     */
    public function getCountry();

    /**
     * Set Country
     *
     * @param string $country
     * @return $this
     */
    public function setCountry($country);

    /**
     * Get Region
     *
     * @return string|null
     */
    public function getRegion();

    /**
     * Set Region
     *
     * @param string $region
     * @return $this
     */
    public function setRegion($region);

    /**
     * Get Postal Code
     *
     * @return string|null
     */
    public function getPostalcode();

    /**
     * Set Postal Code
     *
     * @param string $postalcode
     * @return $this
     */
    public function setPostalcode($postalcode);

    /**
     * Get Telephone
     *
     * @return string|null
     */
    public function getTelephone();

    /**
     * Set Telephone
     *
     * @param string $telephone
     * @return $this
     */
    public function setTelephone($telephone);

    /**
     * Get Tax ID
     *
     * @return string|null
     */
    public function getTaxId();

    /**
     * Set Tax ID
     *
     * @param string $taxId
     * @return $this
     */
    public function setTaxId($taxId);
}
