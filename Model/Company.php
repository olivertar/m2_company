<?php
/**
 * This file is part of the Orangecat Company package.
 *
 * (c) Oliverio Gombert <olivertar@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Orangecat\Company\Model;

use Magento\Framework\Model\AbstractModel;
use Orangecat\Company\Api\Data\CompanyInterface;
use Orangecat\Company\Model\ResourceModel\Company as CompanyResource;

class Company extends AbstractModel implements CompanyInterface
{
    public const STATUS_PENDING = 0;
    public const STATUS_APPROVED = 1;
    public const STATUS_SUSPENDED = 2;
    public const STATUS_REJECTED = 3;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(CompanyResource::class);
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * Set ID
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * Get Name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * Set Name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * Get Email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getData(self::EMAIL);
    }

    /**
     * Set Email
     *
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        return $this->setData(self::EMAIL, $email);
    }

    /**
     * Get Status
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * Set Status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * Get Legal Name
     *
     * @return string|null
     */
    public function getNameLegal()
    {
        return $this->getData(self::NAME_LEGAL);
    }

    /**
     * Set Legal Name
     *
     * @param string $nameLegal
     * @return $this
     */
    public function setNameLegal($nameLegal)
    {
        return $this->setData(self::NAME_LEGAL, $nameLegal);
    }

    /**
     * Get Address
     *
     * @return string|null
     */
    public function getAddress()
    {
        return $this->getData(self::ADDRESS);
    }

    /**
     * Set Address
     *
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        return $this->setData(self::ADDRESS, $address);
    }

    /**
     * Get City
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->getData(self::CITY);
    }

    /**
     * Set City
     *
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        return $this->setData(self::CITY, $city);
    }

    /**
     * Get Country
     *
     * @return string|null
     */
    public function getCountry()
    {
        return $this->getData(self::COUNTRY);
    }

    /**
     * Set Country
     *
     * @param string $country
     * @return $this
     */
    public function setCountry($country)
    {
        return $this->setData(self::COUNTRY, $country);
    }

    /**
     * Get Region
     *
     * @return string|null
     */
    public function getRegion()
    {
        return $this->getData(self::REGION);
    }

    /**
     * Set Region
     *
     * @param string $region
     * @return $this
     */
    public function setRegion($region)
    {
        return $this->setData(self::REGION, $region);
    }

    /**
     * Get Postal Code
     *
     * @return string|null
     */
    public function getPostalcode()
    {
        return $this->getData(self::POSTALCODE);
    }

    /**
     * Set Postal Code
     *
     * @param string $postalcode
     * @return $this
     */
    public function setPostalcode($postalcode)
    {
        return $this->setData(self::POSTALCODE, $postalcode);
    }

    /**
     * Get Telephone
     *
     * @return string|null
     */
    public function getTelephone()
    {
        return $this->getData(self::TELEPHONE);
    }

    /**
     * Set Telephone
     *
     * @param string $telephone
     * @return $this
     */
    public function setTelephone($telephone)
    {
        return $this->setData(self::TELEPHONE, $telephone);
    }

    /**
     * Get Tax ID
     *
     * @return string|null
     */
    public function getTaxId()
    {
        return $this->getData(self::TAX_ID);
    }

    /**
     * Set Tax ID
     *
     * @param string $taxId
     * @return $this
     */
    public function setTaxId($taxId)
    {
        return $this->setData(self::TAX_ID, $taxId);
    }
}
