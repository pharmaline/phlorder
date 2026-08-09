<?php
namespace Pharmaline\Phlorder\Domain\Model;

/***
 *
 * This file is part of the "phlorder Bestellung" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Christian Platt <christian.platt@pharmaline.de>, pharmaline
 *
 ***/

/**
 * Token
 */
class Token extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * phluserfid
     *
     * @var int
     */
    protected $phluserfid = 0;

    /**
     * token
     *
     * @var string
     */
    protected $token = '';

    /**
     * timestamp
     *
     * @var string
     */
    protected $timestamp = '';

    /**
     * status
     *
     * @var string
     */
    protected $status = '';

    /**
     * free
     *
     * @var string
     */
    protected $free = '';

    /**
     * Returns the phluserfid
     *
     * @return int $phluserfid
     */
    public function getPhluserfid()
    {
        return $this->phluserfid;
    }

    /**
     * Sets the phluserfid
     *
     * @param int $phluserfid
     * @return void
     */
    public function setPhluserfid($phluserfid)
    {
        $this->phluserfid = $phluserfid;
    }

    /**
     * Returns the token
     *
     * @return string $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Sets the token
     *
     * @param string $token
     * @return void
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Returns the timestamp
     *
     * @return string $timestamp
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Sets the timestamp
     *
     * @param string $timestamp
     * @return void
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    /**
     * Returns the status
     *
     * @return string $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status
     *
     * @param string $status
     * @return void
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Returns the free
     *
     * @return string $free
     */
    public function getFree()
    {
        return $this->free;
    }

    /**
     * Sets the free
     *
     * @param string $free
     * @return void
     */
    public function setFree($free)
    {
        $this->free = $free;
    }
}
